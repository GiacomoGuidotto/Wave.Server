import React from 'react';
import {createRoot} from 'react-dom/client';
import MockClient from './MockClient';
import './index.css';

const container = document.getElementById('root');
const root = createRoot(container);
root.render(
    <React.StrictMode>
        <MockClient/>
    </React.StrictMode>
);