'use strict';
import {createStore, applyMiddleware, compose} from 'redux';
// import thunkMiddleware from 'redux-thunk';
import rootReducer from './index'; //

const thunkMiddleware = require('redux-thunk').thunk
const createStoreWithMiddleware = applyMiddleware(thunkMiddleware)(createStore);

export default function configureStore(initialState) {
    const store = createStoreWithMiddleware(rootReducer, initialState)
    return store;
}
