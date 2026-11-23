import { replaceable, useSkin } from '@/themes/skins';
import Translate from '@/components/elements/Translate';

export interface PageFooterProps {
    className?: string;
}

/** The copyright line below each page's content. Replace it with a component that renders nothing to drop it. */
function CorePageFooter({ className }: PageFooterProps) {
    const ui = useSkin('PageFooter', {});

    return (
        <div {...ui('root', className)}>
            <p {...ui('text')}>
                <Translate
                    i18nKey='<link>Pterodactyl®</link> © 2015 - <year>{{year}}</year>'
                    values={{ year: new Date().getFullYear() }}
                    components={{
                        link: (
                            <a
                                rel='noopener nofollow noreferrer'
                                href='https://pterodactyl.io'
                                target='_blank'
                                {...ui('link')}
                            />
                        ),
                        year: <span suppressHydrationWarning />,
                    }}
                />
            </p>
        </div>
    );
}

const PageFooter = replaceable('PageFooter', CorePageFooter);

export default PageFooter;
