// here is my reducers file. I don't want any confusion
import { combineReducers } from 'redux'
// this is a standard reducer, same as you've been using since kindergarten
// with action types like LOGIN_SUCCESS, LOGIN_FAIL
import loginReducer from './components/Auth/login_reducer'
import navReducer from './navigation/nav_reducer'

export default combineReducers({
    auth: loginReducer,
    nav: navReducer
})
