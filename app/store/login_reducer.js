
const INITIAL_STATE = {

}

export default (state = INITIAL_STATE, action) => {
    switch (action.type) {
        case 'LOGOUT':
        {
            return {
                ...INITIAL_STATE,
                hasLogin: false
            }
        }
    }
}
