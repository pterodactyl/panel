import { useParams } from '@tanstack/react-router';
import { NewLinkButton } from '@/components/elements/NewButton';

const AddSubuserButton = () => {
    const { id } = useParams({ strict: false });

    return <NewLinkButton to={`/server/${id}/users/new`}>New user</NewLinkButton>;
};

export default AddSubuserButton;
