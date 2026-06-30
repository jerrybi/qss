'use strict';

import {combineReducers} from 'redux';
import loginIn from './loginReducer';

const rootReducer = combineReducers({
    loginIn: loginIn, // 登录类型状态
});

export default rootReducer;
