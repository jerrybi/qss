import {createStore} from "redux";

const initialState = {
    hasLogin: false
}

const reducer = (state = initialState, action) =>{
    switch(action.type){
        case 'hasLogin':
        {
            return {
                ...state,
                hasLogin: action.payload
            }
        }
    }
    return state;
}

const store = createStore(reducer)

export default store;
