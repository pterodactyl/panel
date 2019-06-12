import { FlashMessage, ReduxReducerAction } from '@/redux/types';
import { CLEAR_ALL_FLASH_MESSAGES, PUSH_FLASH_MESSAGE, REMOVE_FLASH_MESSAGE } from '@/redux/actions/flash';

export default (state: FlashMessage[] = [], action: ReduxReducerAction) => {
    switch (action.type) {
        case PUSH_FLASH_MESSAGE:
            return [ ...state.filter(flash => {
                if (action.payload.id && flash.id) {
                    return flash.id !== action.payload.id;
                }

                return true;
            }), action.payload ];
        case REMOVE_FLASH_MESSAGE:
            return [ ...state.filter(flash => flash.id !== action.payload) ];
        case CLEAR_ALL_FLASH_MESSAGES:
            return [];
        default:
            return [ ...state ];
    }
};
