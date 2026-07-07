import axios, { AxiosProgressEvent } from 'axios';
import getFileUploadUrl from '@/api/server/files/getFileUploadUrl';
import createDirectory from '@/api/server/files/createDirectory';
import tw from 'twin.macro';
import { Button } from '@/components/elements/button/index';
import React, { useEffect, useRef } from 'react';
import { ModalMask } from '@/components/elements/Modal';
import Fade from '@/components/elements/Fade';
import useEventListener from '@/plugins/useEventListener';
import { useFlashKey } from '@/plugins/useFlash';
import useFileManagerSwr from '@/plugins/useFileManagerSwr';
import { ServerContext } from '@/state/server';
import { WithClassname } from '@/components/types';
import Portal from '@/components/elements/Portal';
import { CloudUploadIcon, DocumentIcon, FolderIcon } from '@heroicons/react/outline';
import { useSignal } from '@preact/signals-react';

interface FileWithPath {
    file: File;
    relativePath: string;
}

function isFileOrDirectory(event: DragEvent): boolean {
    if (!event.dataTransfer?.types) {
        return false;
    }

    return event.dataTransfer.types.some((value) => value.toLowerCase() === 'files');
}

async function getAllFilesFromEntry(entry: FileSystemEntry, path = ''): Promise<FileWithPath[]> {
    if (entry.isFile) {
        return new Promise((resolve, reject) => {
            (entry as FileSystemFileEntry).file((file) => resolve([{ file, relativePath: path + file.name }]), reject);
        });
    }

    if (entry.isDirectory) {
        const dirEntry = entry as FileSystemDirectoryEntry;
        const reader = dirEntry.createReader();
        const allEntries: FileSystemEntry[] = [];

        await new Promise<void>((resolve, reject) => {
            const readBatch = () => {
                reader.readEntries((entries) => {
                    if (!entries.length) {
                        resolve();
                    } else {
                        allEntries.push(...entries);
                        readBatch();
                    }
                }, reject);
            };

            readBatch();
        });

        const results = await Promise.all(allEntries.map((e) => getAllFilesFromEntry(e, `${path}${dirEntry.name}/`)));
        return results.flat();
    }

    return [];
}

export default ({ className }: WithClassname) => {
    const fileUploadInput = useRef<HTMLInputElement>(null);
    const folderUploadInput = useRef<HTMLInputElement>(null);
    const dropdownRef = useRef<HTMLDivElement>(null);

    const visible = useSignal(false);
    const showDropdown = useSignal(false);
    const timeouts = useSignal<NodeJS.Timeout[]>([]);

    const { mutate } = useFileManagerSwr();
    const { clearAndAddHttpError } = useFlashKey('files');

    const uuid = ServerContext.useStoreState((state) => state.server.data!.uuid);
    const directory = ServerContext.useStoreState((state) => state.files.directory);
    const { clearFileUploads, removeFileUpload, pushFileUpload, setUploadProgress } = ServerContext.useStoreActions(
        (actions) => actions.files
    );

    useEventListener(
        'dragenter',
        (e) => {
            e.preventDefault();
            e.stopPropagation();
            if (isFileOrDirectory(e)) {
                visible.value = true;
            }
        },
        { capture: true }
    );

    useEventListener('dragexit', () => (visible.value = false), { capture: true });

    useEventListener('keydown', () => {
        visible.value = false;
        showDropdown.value = false;
    });

    useEffect(() => {
        folderUploadInput.current?.setAttribute('webkitdirectory', '');
    }, []);

    useEffect(() => {
        const handler = (e: MouseEvent) => {
            if (dropdownRef.current && !dropdownRef.current.contains(e.target as Node)) {
                showDropdown.value = false;
            }
        };

        document.addEventListener('click', handler);
        return () => document.removeEventListener('click', handler);
    }, []);

    useEffect(() => {
        return () => timeouts.value.forEach(clearTimeout);
    }, []);

    const onUploadProgress = (data: AxiosProgressEvent, name: string) => {
        setUploadProgress({ name, loaded: data.loaded });
    };

    const uploadFilesWithPaths = async (filesWithPaths: FileWithPath[]) => {
        clearAndAddHttpError();

        // Collect unique subdirectory paths, shallowest first, so parents exist before children.
        const dirSet = new Set<string>();
        for (const { relativePath } of filesWithPaths) {
            const parts = relativePath.split('/');
            for (let i = 1; i < parts.length; i++) {
                dirSet.add(parts.slice(0, i).join('/'));
            }
        }

        const base = directory.replace(/\/$/, '');
        const dirsToCreate = Array.from(dirSet).sort((a, b) => a.split('/').length - b.split('/').length);
        for (const subDir of dirsToCreate) {
            const lastSlash = subDir.lastIndexOf('/');
            const dirRoot = lastSlash < 0 ? directory : `${base}/${subDir.substring(0, lastSlash)}`;
            const dirName = lastSlash < 0 ? subDir : subDir.substring(lastSlash + 1);

            try {
                await createDirectory(uuid, dirRoot, dirName);
            } catch {
                // Directory may already exist — creation failures here shouldn't block the upload.
            }
        }

        for (const { file, relativePath } of filesWithPaths) {
            const controller = new AbortController();
            const lastSlash = relativePath.lastIndexOf('/');
            const subDir = lastSlash > 0 ? relativePath.substring(0, lastSlash) : '';
            const targetDirectory = subDir ? `${base}/${subDir}` : directory;

            pushFileUpload({
                name: relativePath,
                data: { abort: controller, loaded: 0, total: file.size },
            });

            try {
                const url = await getFileUploadUrl(uuid);
                await axios.post(
                    url,
                    { files: file },
                    {
                        signal: controller.signal,
                        headers: { 'Content-Type': 'multipart/form-data' },
                        params: { directory: targetDirectory },
                        onUploadProgress: (data) => onUploadProgress(data, relativePath),
                    }
                );

                timeouts.value.push(setTimeout(() => removeFileUpload(relativePath), 500));
            } catch (error: unknown) {
                clearFileUploads();
                clearAndAddHttpError(error instanceof Error ? error : String(error));
                return;
            }
        }

        mutate();
    };

    const handleDropItems = async (items: DataTransferItemList) => {
        const entries = Array.from(items)
            .filter((item) => item.kind === 'file')
            .map((item) => item.webkitGetAsEntry())
            .filter((entry): entry is FileSystemEntry => entry !== null);

        const results = await Promise.all(entries.map((entry) => getAllFilesFromEntry(entry)));
        uploadFilesWithPaths(results.flat());
    };

    return (
        <>
            <Portal>
                <Fade appear in={visible.value} timeout={75} key={'upload_modal_mask'} unmountOnExit>
                    <ModalMask
                        onClick={() => (visible.value = false)}
                        onDragOver={(e) => e.preventDefault()}
                        onDrop={(e) => {
                            e.preventDefault();
                            e.stopPropagation();

                            visible.value = false;
                            if (e.dataTransfer?.items.length) {
                                handleDropItems(e.dataTransfer.items);
                            }
                        }}
                    >
                        <div className={'w-full flex items-center justify-center pointer-events-none'}>
                            <div
                                className={
                                    'flex items-center space-x-4 bg-black w-full ring-4 ring-blue-200 ring-opacity-60 rounded p-6 mx-10 max-w-sm'
                                }
                            >
                                <CloudUploadIcon className={'w-10 h-10 flex-shrink-0'} />
                                <p className={'font-header flex-1 text-lg text-neutral-100 text-center'}>
                                    Drag and drop files or folders to upload.
                                </p>
                            </div>
                        </div>
                    </ModalMask>
                </Fade>
            </Portal>
            <input
                type={'file'}
                ref={fileUploadInput}
                css={tw`hidden`}
                onChange={(e) => {
                    if (!e.currentTarget.files) return;

                    const list = Array.from(e.currentTarget.files);
                    uploadFilesWithPaths(list.map((file) => ({ file, relativePath: file.name })));
                    e.currentTarget.value = '';
                }}
                multiple
            />
            <input
                type={'file'}
                ref={folderUploadInput}
                css={tw`hidden`}
                onChange={(e) => {
                    if (!e.currentTarget.files) return;

                    const list = Array.from(e.currentTarget.files);
                    uploadFilesWithPaths(list.map((file) => ({ file, relativePath: file.webkitRelativePath || file.name })));
                    e.currentTarget.value = '';
                }}
            />
            <div ref={dropdownRef} className={'relative'}>
                <Button className={className} onClick={() => (showDropdown.value = !showDropdown.value)}>
                    Upload
                </Button>
                {showDropdown.value && (
                    <div
                        className={
                            'absolute right-0 top-full mt-1 bg-neutral-800 border border-neutral-700 rounded shadow-lg z-50 min-w-max overflow-hidden'
                        }
                    >
                        <button
                            className={
                                'flex items-center space-x-2 w-full px-4 py-2 text-sm text-neutral-200 hover:bg-neutral-700'
                            }
                            onClick={() => {
                                showDropdown.value = false;
                                fileUploadInput.current?.click();
                            }}
                        >
                            <DocumentIcon className={'w-4 h-4'} />
                            <span>Files</span>
                        </button>
                        <button
                            className={
                                'flex items-center space-x-2 w-full px-4 py-2 text-sm text-neutral-200 hover:bg-neutral-700'
                            }
                            onClick={() => {
                                showDropdown.value = false;
                                folderUploadInput.current?.click();
                            }}
                        >
                            <FolderIcon className={'w-4 h-4'} />
                            <span>Folder</span>
                        </button>
                    </div>
                )}
            </div>
        </>
    );
};
