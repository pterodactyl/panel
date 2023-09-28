import { useRef } from 'react';
import { useVirtualizer } from '@tanstack/react-virtual';
import { FileObject } from '@/api/server/files/loadDirectory';
import MassActionsBar from './MassActionsBar';
import FileObjectRow from './FileObjectRow';

export default function FileListVirtualized({ files }: { files: FileObject[] }) {
    const parrent = useRef<HTMLDivElement>(null);

    const rowVirtualizer = useVirtualizer({
        count: files.length,
        getScrollElement: () => parrent.current,
        estimateSize: () => 35,
    });

    return (
        <div ref={parrent}>
            {rowVirtualizer.getVirtualItems().map(virtualItem => {
                const file = files[virtualItem.index];
                if (!file) return null;
                return <FileObjectRow file={file} key={virtualItem.key} />;
            })}
            <MassActionsBar />
        </div>
    );
}
