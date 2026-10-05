import { createContext, useContext } from 'react';
import type { FileManagerSlotData } from '@/extensions/registry';

export const FileManagerExtensionContext = createContext<FileManagerSlotData | null>(null);

export default function useFileManagerExtensionData(): FileManagerSlotData | null {
    return useContext(FileManagerExtensionContext);
}
