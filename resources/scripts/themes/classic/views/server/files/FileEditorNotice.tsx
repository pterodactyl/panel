import { cn, Translate } from '@/sdk';
import type { ViewProps } from '@/sdk/theme';

const codeClass = 'font-mono bg-muted rounded-sm py-px px-1';

export default function FileEditorNotice({ model, className }: ViewProps<'server.files.editor.notice'>) {
    return model.name.endsWith('.pteroignore') ? (
        <div className={cn('mb-4 p-4 border-l-4 bg-muted rounded-sm border-accent', className)}>
            <p className='text-muted-foreground text-sm'>
                <Translate
                    i18nKey="You're editing a <code>{{file}}</code> file. Any files or directories listed in here will be excluded from backups. Wildcards are supported by using an asterisk (<code>{{wildcard}}</code>). You can negate a prior rule by prepending an exclamation point (<code>{{negation}}</code>)."
                    values={{ file: '.pteroignore', wildcard: '*', negation: '!' }}
                    components={{ code: <code className={codeClass} /> }}
                />
            </p>
        </div>
    ) : null;
}
