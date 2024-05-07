import React, { useRef, useState, useEffect } from 'react';
import PropTypes from 'prop-types';

import Button from '@components/Button';

import './ClearFiltersButton.scss';

function ClearFiltersButton( props ) {
  const $self = useRef();
  //const [showUndo, setShowUndo] = useState( false );

  const handleClick = () => {

    //if ( showUndo ) {
      //props.undoClearFilters();
      //$self.current.style.cssText = 'height: 0; padding: 0; line-height: 0; margin-top: -.25rem; margin-bottom: -.25rem';
    //} else {
      props.clearFilters();
      $self.current.style.cssText = 'height: 0; padding: 0; line-height: 0; margin-top: -.25rem; margin-bottom: -.25rem';
    //}

    //setShowUndo( !showUndo );
  };

  useEffect( () => {
    if ( !props.hasInteractedWithFilters && !props.showClearFiltersInitially ) {
      $self.current.style.cssText = 'height: 0; padding: 0; line-height: 0; margin-top: -.25rem; margin-bottom: -.25rem';
    } else {
      $self.current.style.cssText = '';
    }
  }, [props.hasInteractedWithFilters, props.showClearFiltersInitially] );

  useEffect( () => {
    //$self.current.style.cssText = '';
    //$self.current.style.cssText = 'height: 0; padding: 0; line-height: 0; margin-top: -.25rem; margin-bottom: -.25rem';
    //setShowUndo( !showUndo );
    //$self.current.style.cssText = 'height: 0; padding: 0; line-height: 0; margin-top: -.25rem; margin-bottom: -.25rem';
    //$self.current.style.cssText = '';
    if ( !props.hasInteractedWithFilters && !props.lastInteractedWithFilters ) {
      $self.current.style.cssText = 'height: 0; padding: 0; line-height: 0; margin-top: -.25rem; margin-bottom: -.25rem';
    } else {
      $self.current.style.cssText = '';
    }
  }, [props.hasInteractedWithFilters, props.lastInteractedWithFilters] );

  return (
    <Button
      ref={ $self }
      type="submit"
      data-testid="ml-clear-filters-button"
      className={ `ml-clear-filters-button${
        props.className ? ` ${props.className}` : ''
      }${
        ( props.hasInteractedWithFilters || props.showClearFiltersInitially ) ? ' ml-clear-filters-button--has-interacted-with-filters' : ''
      }` }
      onClick={ handleClick }
      aria-hidden={ ( !props.hasInteractedWithFilters ).toString() }
      aria-expanded={ props.hasInteractedWithFilters.toString() }
      aria-live="assertive"
    >
      <span className="ml-clear-filters-button__icon" aria-hidden="true">&times;</span>{ ' ' }
      { <span className="ml-clear-filters-button__text">Clear filters</span> }
    </Button>
  );
}

ClearFiltersButton.displayName = 'ClearFiltersButton';

ClearFiltersButton.propTypes = {
  "children": PropTypes.node,
  "className": PropTypes.string,
  "clearFilters": PropTypes.func.isRequired,
  "undoClearFilters": PropTypes.func.isRequired,
  "hasInteractedWithFilters": PropTypes.bool.isRequired,
  "showClearFiltersInitially": PropTypes.bool,
  "lastInteractedWithFilters": PropTypes.number,
};

export default ClearFiltersButton;
