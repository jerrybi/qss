'use strict';
import * as types from '../constants/loginTypes';

const initialState = {
    hasLogin: false,
    user: null
};

export default function loginIn(state = initialState, action) {
    switch (action.type) {
        case types.LOGIN_IN:
            return {
                ...state,
                hasLogin: true,
                user: action.user
            };
            break;
        default:
            return state;
    }
}
