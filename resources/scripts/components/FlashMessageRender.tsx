import * as React from 'react';
import { FlashMessage, ReduxState } from '@/redux/types';
import { connect } from 'react-redux';
import MessageBox from '@/components/MessageBox';

type Props = Readonly<{
    spacerClass?: string;
    flashes: FlashMessage[];
}>;

class FlashMessageRender extends React.PureComponent<Props> {
    render () {
        if (this.props.flashes.length === 0) {
            return null;
        }

        return (
            <React.Fragment>
                {
                    this.props.flashes.map((flash, index) => (
                        <React.Fragment key={flash.id || flash.type + flash.message}>
                            {index > 0 && <div className={this.props.spacerClass || 'mt-2'}></div>}
                            <MessageBox type={flash.type} title={flash.title}>
                                {flash.message}
                            </MessageBox>
                        </React.Fragment>
                    ))
                }
            </React.Fragment>
        );
    }
}

const mapStateToProps = (state: ReduxState) => ({
    flashes: state.flashes,
});

export default connect(mapStateToProps)(FlashMessageRender);
