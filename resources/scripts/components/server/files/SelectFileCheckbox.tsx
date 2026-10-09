import { useServerDirectory, useServerStore } from '@/state/server';
import Checkbox from '@/components/ui/Checkbox';

export default function SelectFileCheckbox({ name }: { name: string }) {
    const directory = useServerDirectory();
    const isChecked = useServerStore(
        (state) => state.files.selectedDirectory === directory && state.files.selectedFiles.includes(name)
    );
    const appendSelectedFile = useServerStore((state) => state.files.appendSelectedFile);
    const removeSelectedFile = useServerStore((state) => state.files.removeSelectedFile);

    return (
        <div className='flex-none px-4 py-2 absolute self-center z-30'>
            <Checkbox
                name='selectedFiles'
                value={name}
                aria-label={`Select ${name}`}
                checked={isChecked}
                onChange={(checked) =>
                    checked ? appendSelectedFile({ directory, name }) : removeSelectedFile({ directory, name })
                }
            />
        </div>
    );
}
