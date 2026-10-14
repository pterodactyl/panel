import { useMemo } from 'react';
import { ArrowDownCircle, Clock, Code, FileArchive, ListTodo, ToggleRight, type LucideIcon } from 'lucide-react';
import {
    actionsColumn,
    Badge,
    DeleteAction,
    Dialog,
    EditAction,
    Empty,
    EmptyContent,
    EmptyDescription,
    EmptyHeader,
    EmptyMedia,
    EmptyTitle,
    Icon,
    RowActions,
    SpinnerOverlay,
    Table,
    type ScheduleTask,
    type ScheduleTasksModel,
    type TableColumn,
} from '@/sdk';
import type { ViewProps } from '@/sdk/theme';
import NewTaskButton from './NewTaskButton';
import TaskDialog from './TaskDialog';

const actionDetails = (action: string): [string, LucideIcon] => {
    switch (action) {
        case 'command':
            return ['Send Command', Code];
        case 'power':
            return ['Send Power Action', ToggleRight];
        case 'backup':
            return ['Create Backup', FileArchive];
        default:
            return ['Unknown Action', Code];
    }
};

function TaskIdentity({ task }: { task: ScheduleTask }) {
    const [title, icon] = actionDetails(task.action);

    return (
        <div className='flex min-w-48 items-start gap-3'>
            <Icon icon={icon} className='mt-0.5 hidden text-muted-foreground md:block' />
            <div className='min-w-0'>
                <p className='font-medium'>{title}</p>
                {task.payload ? (
                    <code className='mt-1 block max-w-xl whitespace-pre-wrap break-all text-xs text-muted-foreground'>
                        {task.payload}
                    </code>
                ) : null}
            </div>
        </div>
    );
}

function TaskActions({ model, task }: { model: ScheduleTasksModel; task: ScheduleTask }) {
    const name = `task ${task.sequence} (${actionDetails(task.action)[0]})`;

    return (
        <RowActions>
            {model.canUpdate && (
                <>
                    <Dialog.Trigger
                        trigger={({ onClick }) => <EditAction aria-label={`Edit ${name}`} onClick={onClick} />}
                    >
                        {(dialog) => (
                            <TaskDialog
                                {...dialog}
                                form={model.form}
                                task={task}
                                onSubmit={(values) => model.actions.update(task.id, values)}
                            />
                        )}
                    </Dialog.Trigger>
                    <Dialog.ConfirmTrigger
                        title='Confirm task deletion'
                        confirm='Delete Task'
                        onConfirmed={async (_event, close) => {
                            if (await model.actions.remove(task.id)) {
                                close();
                            }
                        }}
                        trigger={({ onClick }) => <DeleteAction aria-label={`Delete ${name}`} onClick={onClick} />}
                    >
                        Are you sure you want to delete this task? This action cannot be undone.
                    </Dialog.ConfirmTrigger>
                </>
            )}
        </RowActions>
    );
}

function columns(model: ScheduleTasksModel): TableColumn<ScheduleTask>[] {
    return [
        {
            id: 'action',
            label: 'Task',
            sortValue: (task) => actionDetails(task.action)[0],
            cell: (task) => <TaskIdentity task={task} />,
        },
        {
            id: 'delay',
            label: 'Delay',
            sortValue: (task) => task.timeOffset,
            cell: (task) =>
                task.sequence > 1 && task.timeOffset > 0 ? (
                    <span className='inline-flex items-center gap-1.5 whitespace-nowrap text-xs text-muted-foreground'>
                        <Icon icon={Clock} className='h-3.5 w-3.5' />
                        {task.timeOffset}s later
                    </span>
                ) : (
                    '-'
                ),
            headerClassName: 'hidden md:table-cell w-32',
            cellClassName: 'hidden md:table-cell w-32',
        },
        {
            id: 'continue_on_failure',
            label: 'Failure behavior',
            cell: (task) =>
                task.continueOnFailure ? (
                    <Badge tone='warning' className='inline-flex items-center gap-1.5 whitespace-nowrap rounded-full'>
                        <Icon icon={ArrowDownCircle} className='h-3.5 w-3.5' />
                        Continues
                    </Badge>
                ) : (
                    <span className='text-xs text-muted-foreground'>Stops</span>
                ),
            headerClassName: 'hidden lg:table-cell w-36',
            cellClassName: 'hidden lg:table-cell w-36',
        },
        actionsColumn(2, (task) => <TaskActions model={model} task={task} />),
    ];
}

export default function ScheduleTasks({ model, className }: ViewProps<'server.schedule.tasks'>) {
    const tableColumns = useMemo(() => columns(model), [model]);

    return (
        <>
            <SpinnerOverlay visible={model.deleting} fixed size='large' />
            <Table
                rows={model.tasks}
                columns={tableColumns}
                keyOf={(task) => String(task.id)}
                className={className}
                emptyState={
                    <Empty size='compact'>
                        <EmptyHeader>
                            <EmptyMedia variant='icon'>
                                <ListTodo />
                            </EmptyMedia>
                            <EmptyTitle>No tasks</EmptyTitle>
                            <EmptyDescription>
                                Add a task to tell this schedule what to do when it runs.
                            </EmptyDescription>
                        </EmptyHeader>
                        {model.canUpdate && (
                            <EmptyContent>
                                <NewTaskButton form={model.form} onSubmit={model.actions.create} />
                            </EmptyContent>
                        )}
                    </Empty>
                }
            />
        </>
    );
}
