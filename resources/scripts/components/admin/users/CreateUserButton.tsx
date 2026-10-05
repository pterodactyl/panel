import { NewLinkButton } from '@/components/elements/NewButton';

export default function CreateUserButton() {
    return <NewLinkButton to={'/panel/users/new'}>New user</NewLinkButton>;
}
