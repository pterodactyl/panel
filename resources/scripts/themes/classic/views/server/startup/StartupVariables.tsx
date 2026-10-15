import { useState } from 'react';
import { Variable } from 'lucide-react';
import {
    Badge,
    Empty,
    EmptyDescription,
    EmptyHeader,
    EmptyMedia,
    EmptyTitle,
    Input,
    InputSpinner,
    Select,
    Switch,
    TitledGreyBox,
    useDebouncedCallback,
} from '@/sdk';
import type { StartupVariable, StartupVariablesModel, ViewProps } from '@/sdk/theme';
import { usePending } from '../usePending';

function VariableControl({ model, variable }: { model: StartupVariablesModel; variable: StartupVariable }) {
    const [draft, setDraft] = useState<string | null>(null);
    const [search, setSearch] = useState('');
    const [saving, track] = usePending();
    const value = draft ?? variable.value;
    const editable = model.canEdit && variable.editable;
    const { control } = variable;
    const optionListId = `startup-variable-options-${variable.key.toLowerCase().replace(/[^a-z0-9_-]/g, '-')}`;
    // The update of the edit's own model, so an edit flushed after the server changes saves to its server.
    const save = useDebouncedCallback((update: StartupVariablesModel['update'], next: string) => {
        const saved = update(variable.key, next);

        track(saved);
        // A newer draft keeps showing until it has saved too.
        void saved.then(
            () => setDraft((current) => (current === next ? null : current)),
            () => {}
        );
    }, 500);
    const change = (next: string) => {
        setDraft(next);
        save(model.update, next);
    };

    const suggestionOptions =
        control.kind === 'suggest'
            ? control.options.filter((option) => {
                  if (search.trim() === '') {
                      return true;
                  }

                  const query = search.toLowerCase();

                  return option.label.toLowerCase().includes(query) || option.value.toLowerCase().includes(query);
              })
            : [];

    return (
        <InputSpinner visible={saving}>
            {control.kind === 'switch' && (
                <Switch
                    disabled={!editable}
                    checked={value === control.on}
                    onChange={() => editable && change(value === control.on ? control.off : control.on)}
                />
            )}
            {control.kind === 'select' && (
                <Select
                    value={value}
                    onChange={(selected) => change(String(selected))}
                    disabled={!editable}
                    options={control.options.map((option) => ({ value: option, label: option }))}
                />
            )}
            {control.kind === 'text' && (
                <Input
                    onChange={(event) => editable && change(event.currentTarget.value)}
                    onBlur={save.flush}
                    readOnly={!editable}
                    name={variable.key}
                    value={value}
                    placeholder={variable.defaultValue}
                />
            )}
            {control.kind === 'suggest' &&
                (control.options.length === 0 ? (
                    <>
                        <Input
                            list={optionListId}
                            onChange={(event) => editable && change(event.currentTarget.value)}
                            onBlur={save.flush}
                            readOnly={!editable}
                            name={variable.key}
                            value={value}
                            placeholder={variable.defaultValue}
                        />
                        <datalist id={optionListId}>
                            {control.options.map((option) => (
                                <option key={option.value} value={option.value}>
                                    {option.label}
                                </option>
                            ))}
                        </datalist>
                    </>
                ) : (
                    <Select
                        value={value}
                        onChange={(selected) => change(String(selected))}
                        onSearchChange={(query) => {
                            setSearch(query);
                            if (control.allowManualInput && query !== '') {
                                change(query);
                            }
                        }}
                        onBlur={save.flush}
                        disabled={!editable}
                        options={suggestionOptions}
                    />
                ))}
        </InputSpinner>
    );
}

function VariableBox({ model, variable }: { model: StartupVariablesModel; variable: StartupVariable }) {
    return (
        <TitledGreyBox
            title={
                <p className='text-sm uppercase'>
                    {!variable.editable && <Badge className='bg-card rounded-full mr-2 mb-1'>Read Only</Badge>}
                    {variable.name}
                </p>
            }
        >
            <VariableControl model={model} variable={variable} />
            <p className='mt-1 text-xs text-muted-foreground'>{variable.description}</p>
        </TitledGreyBox>
    );
}

export default function StartupVariables({ model, className }: ViewProps<'server.startup.variables'>) {
    return (
        <div className={className}>
            <h3 className='mt-8 mb-2 text-2xl'>Variables</h3>
            {model.variables.length === 0 ? (
                <Empty size='compact' className='border'>
                    <EmptyHeader>
                        <EmptyMedia variant='icon'>
                            <Variable />
                        </EmptyMedia>
                        <EmptyTitle>No variables</EmptyTitle>
                        <EmptyDescription>
                            This server doesn&apos;t have any startup variables to configure.
                        </EmptyDescription>
                    </EmptyHeader>
                </Empty>
            ) : (
                <div className='grid gap-8 md:grid-cols-2'>
                    {model.variables.map((variable) => (
                        <VariableBox key={variable.key} model={model} variable={variable} />
                    ))}
                </div>
            )}
        </div>
    );
}
