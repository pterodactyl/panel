import * as React from 'react';
import { FlashMessage, ReduxState } from '@/redux/types';
import { connect } from 'react-redux';
import MessageBox from '@/components/MessageBox';

type Props = Readonly<{
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
                    this.props.flashes.map(flash => (
                        <MessageBox
                            key={flash.id || flash.type + flash.message}
                            type={flash.type}
                            title={flash.title}
                        >
                            {flash.message}
                        </MessageBox>
                    ))
                }
            </React.Fragment>
        )
    }
}

const mapStateToProps = (state: ReduxState) => ({
    flashes: state.flashes,
});

export default connect(mapStateToProps)(FlashMessageRender);
