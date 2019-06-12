import { combineReducers } from 'redux';
import flashReducer from './reducers/flash';
import { ReduxState } from '@/redux/types';

export const reducers = combineReducers<ReduxState>({
    flashes: flashReducer,
});
