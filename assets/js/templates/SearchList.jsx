import React from 'react';
import PropTypes from 'prop-types';
import classNames from 'classnames';
import { useLocation, useHistory } from 'react-router-dom';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { makeEntriesLink } from '../helpers/uri';
import * as icons from '../icons';


function splitTerm(term) {
    if (term == '') {
        return [];
    } else if (term.match(/^\/.+\/$/)) {
        return [term];
    }

    var words = term.match(/"[^"]+"|\S+/g);
    for (var i = 0; i < words.length; i++) {
        words[i] = words[i].replace(/"/g, '');
    }
    return words;
}


function joinTerm(words) {
    if (!words || words.length <= 0) {
        return '';
    }
    for (var i = 0; i < words.length; i++) {
        if (words[i].indexOf(' ') >= 0) {
            words[i] = '"'  + words[i] + '"';
        }
    }
    return words.join(' ');
}


// remove search term
function handleRemove({ index, location, history, regexSearch }) {
    let newterm;
    if (regexSearch) {
        newterm = '';
    } else {
        const queryString = new URLSearchParams(location.search);
        const oldTerm = queryString.get('search');

        const termArray = splitTerm(oldTerm);
        termArray.splice(index, 1);
        newterm = joinTerm(termArray);
    }

    history.push(makeEntriesLink(location, { search: newterm, id: null }));
}


function SearchWord({ regexSearch, index, item }) {
    const location = useLocation();
    const history = useHistory();

    const removeOnClick = React.useCallback(
        () => handleRemove({ index, location, history, regexSearch }),
        [index, location, history, regexSearch]
    );

    return (
        <li onClick={removeOnClick} className={classNames({ 'regex-search-term': regexSearch })}>
            {item} <FontAwesomeIcon icon={icons.remove} />
        </li>
    );
}

SearchWord.propTypes = {
    regexSearch: PropTypes.bool.isRequired,
    index: PropTypes.number.isRequired,
    item: PropTypes.string.isRequired,
};


/**
 * Component for showing list of search terms at the top of the page.
 */
export default function SearchList() {
    const location = useLocation();

    const searchText = React.useMemo(() => {
        const queryString = new URLSearchParams(location.search);

        return queryString.get('search') ?? '';
    }, [location.search]);

    const regexSearch = searchText.match(/^\/.+\/$/) !== null;
    const terms = regexSearch ? [searchText] : splitTerm(searchText);

    return (
        terms.map((item, index) =>
            <SearchWord
                key={index}
                index={index}
                item={item}
                regexSearch={regexSearch}
            />
        )
    );
}
