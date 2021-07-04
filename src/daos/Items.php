<?php

namespace daos;

use helpers\Authentication;
use helpers\Configuration;
use Monolog\Logger;

/**
 * Class for accessing persistent saved items
 *
 * @copyright  Copyright (c) Tobias Zeising (http://www.aditu.de)
 * @license    GPLv3 (https://www.gnu.org/licenses/gpl-3.0.html)
 * @author     Harald Lapp <harald.lapp@gmail.com>
 * @author     Tobias Zeising <tobias.zeising@aditu.de>
 */
class Items {
    /** @var ItemsInterface Instance of backend specific items class */
    private $backend;

    /** @var Authentication authentication helper */
    private $authentication;

    /** @var Configuration configuration */
    private $configuration;

    /** @var Logger */
    private $logger;

    /**
     * Constructor.
     *
     * @return void
     */
    public function __construct(Authentication $authentication, Configuration $configuration, Logger $logger, ItemsInterface $backend) {
        $this->authentication = $authentication;
        $this->backend = $backend;
        $this->configuration = $configuration;
        $this->logger = $logger;
    }

    /**
     * pass any method call to the backend.
     *
     * @param string $name name of the function
     * @param array $args arguments
     *
     * @return mixed methods return value
     */
    public function __call($name, $args) {
        if (method_exists($this->backend, $name)) {
            return call_user_func_array([$this->backend, $name], $args);
        } else {
            $this->logger->error('Unimplemented method for ' . $this->configuration->dbType . ': ' . $name);
        }
    }

    /**
     * cleanup orphaned and old items
     *
     * @param int $days delete all items older than this value [optional]
     *
     * @return void
     */
    public function cleanup($days) {
        $minDate = null;
        if ($days !== 0) {
            $minDate = new \DateTime();
            $minDate->sub(new \DateInterval('P' . $days . 'D'));
        }
        $this->backend->cleanup($minDate);
    }

    /**
     * returns items
     *
     * @param mixed $options search, offset and filter params
     *
     * @return mixed items as array
     */
    public function get($options = []) {
        $options = array_merge(
            [
                'starred' => false,
                'offset' => 0,
                'search' => false,
                'items' => $this->configuration->itemsPerpage,
            ],
            $options
        );

        if (isset($options['fromDatetime']) && strlen($options['fromDatetime']) > 0) {
            $options['fromDatetime'] = new \DateTime($options['fromDatetime']);
        } else {
            unset($options['fromDatetime']);
        }

        if (isset($options['updatedsince']) && strlen($options['updatedsince']) > 0) {
            $options['updatedsince'] = new \DateTime($options['updatedsince']);
        } else {
            unset($options['updatedsince']);
        }

        $items = $this->backend->get($options);

        // remove private posts with private tags
        if (!$this->authentication->showPrivateTags()) {
            foreach ($items as $idx => $item) {
                foreach ($item['tags'] as $tag) {
                    if (strpos(trim($tag), '@') === 0) {
                        unset($items[$idx]);
                        break;
                    }
                }
            }
            $items = array_values($items);
        }

        // remove posts with hidden tags
        if (!isset($options['tag']) || strlen($options['tag']) === 0) {
            foreach ($items as $idx => $item) {
                foreach ($item['tags'] as $tag) {
                    if (strpos(trim($tag), '#') === 0) {
                        unset($items[$idx]);
                        break;
                    }
                }
            }
            $items = array_values($items);
        }

        return $items;
    }
}
