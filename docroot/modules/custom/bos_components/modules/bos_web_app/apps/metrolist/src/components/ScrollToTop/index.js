import { useEffect } from "react";
import { useLocation } from "react-router-dom";
import { getGlobalThis } from '@util/objects';

const globalThis = getGlobalThis();

function ScrollToTop() {
  const { pathname } = useLocation();

  useEffect( () => {
    globalThis.scrollTo( 0, 0 );
  }, [pathname] );

  return null;
}

ScrollToTop.displayName = 'ScrollToTop';

// ScrollToTop.propTypes = {};

export default ScrollToTop;
