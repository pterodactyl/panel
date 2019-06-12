import { FlashMessage } from '@/redux/types';

export const PUSH_FLASH_MESSAGE = 'PUSH_FLASH_MESSAGE';
export const REMOVE_FLASH_MESSAGE = 'REMOVE_FLASH_MESSAGE';
export const CLEAR_ALL_FLASH_MESSAGES = 'CLEAR_ALL_FLASH_MESSAGES';

export const pushFlashMessage = (payload: FlashMessage) => ({
    type: PUSH_FLASH_MESSAGE, payload,
});

export const removeFlashMessage = (id: string) => ({
    type: REMOVE_FLASH_MESSAGE, payload: id,
});

export const clearAllFlashMessages = () => ({
    type: CLEAR_ALL_FLASH_MESSAGES,
});
