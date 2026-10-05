import { Variable } from 'lucide-react';
import { useAdminEggVariables } from '@/api/admin/eggs/queries';
import { useEggDetail } from '@/components/admin/eggs/useEggDetail';
import Spinner from '@/components/elements/Spinner';
import TitledGreyBox from '@/components/elements/TitledGreyBox';
import EggVariableBox from '@/components/admin/eggs/EggVariableBox';
import CreateEggVariableButton from '@/components/admin/eggs/CreateEggVariableButton';
import ListToolbar from '@/components/elements/ListToolbar';
import { httpErrorToHuman } from '@/api/http';
import { Alert } from '@/components/elements/alert';
import Button from '@/components/elements/Button';
import { Empty, EmptyContent, EmptyDescription, EmptyHeader, EmptyMedia, EmptyTitle } from '@/components/ui/empty';
import { emptyCompactClass } from '@/components/ui/styles';

export default function EggVariablesTab() {
    const egg = useEggDetail();
    const { data: variables, error, isFetching, refetch } = useAdminEggVariables(egg.attributes.id);

    return (
        <div>
            <ListToolbar className={'mb-6'}>
                <CreateEggVariableButton eggId={egg.attributes.id} />
            </ListToolbar>
            {!variables ? (
                error && !isFetching ? (
                    <div className={'space-y-4'}>
                        <Alert type={'danger'}>{httpErrorToHuman(error)}</Alert>
                        <Button.Text type={'button'} onClick={() => refetch()}>
                            Retry
                        </Button.Text>
                    </div>
                ) : (
                    <Spinner size={'large'} centered />
                )
            ) : variables.data.length === 0 ? (
                <TitledGreyBox title={'Egg Variables'}>
                    <Empty className={emptyCompactClass}>
                        <EmptyHeader>
                            <EmptyMedia variant={'icon'}>
                                <Variable />
                            </EmptyMedia>
                            <EmptyTitle>No variables</EmptyTitle>
                            <EmptyDescription>
                                Variables let server owners configure the startup command and environment.
                            </EmptyDescription>
                        </EmptyHeader>
                        <EmptyContent>
                            <CreateEggVariableButton eggId={egg.attributes.id} />
                        </EmptyContent>
                    </Empty>
                </TitledGreyBox>
            ) : (
                <div aria-busy={isFetching} className={'grid grid-cols-1 lg:grid-cols-2 gap-6'}>
                    {variables.data.map((variable) => (
                        <EggVariableBox key={variable.attributes.id} eggId={egg.attributes.id} variable={variable} />
                    ))}
                </div>
            )}
        </div>
    );
}
