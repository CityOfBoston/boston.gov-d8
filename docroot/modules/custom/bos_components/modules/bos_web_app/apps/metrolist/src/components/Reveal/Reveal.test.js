import React from 'react';
import { render } from '@testing-library/react';
import Reveal from './index';

it( 'renders', () => {
  render( <Reveal>Lorem ipsum dolor, sit amet consectetur adipisicing elit. Quae, molestiae. Reprehenderit, ea. Eaque quia, laudantium perferendis ex voluptatibus deserunt, veritatis reprehenderit hic, provident enim fugit tempora voluptate laborum. Beatae, recusandae.</Reveal> );
} );
