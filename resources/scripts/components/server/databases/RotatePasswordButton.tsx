import { useCurrentServerUuid } from '@/api/server/queries';
import { rotateServerDatabasePasswordInput, useRotateDatabasePassword } from '@/api/server/databases/queries';
import Button from '@/components/elements/Button';

type Props = {
    databaseId: string;
};

const RotatePasswordButton = ({ databaseId }: Props) => {
    const uuid = useCurrentServerUuid()!;
    const rotatePassword = useRotateDatabasePassword();

    if (!databaseId) {
        return null;
    }

    const rotate = () => {
        rotatePassword.mutate(rotateServerDatabasePasswordInput(uuid, databaseId));
    };

    return (
        <Button isSecondary color={'primary'} className={'mr-2'} onClick={rotate} isLoading={rotatePassword.isPending}>
            Rotate Password
        </Button>
    );
};

export default RotatePasswordButton;
