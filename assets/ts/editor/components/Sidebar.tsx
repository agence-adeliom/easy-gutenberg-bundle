import { createElement } from '@wordpress/element'
import { createSlotFill, Panel } from '@wordpress/components'
import {__} from "@wordpress/i18n";

const { Slot, Fill } = createSlotFill(
    'StandAloneBlockEditorSidebarInspector'
)

const Sidebar = () => {
    return (
        <div
            className="block-editor__sidebar"
            role="region"
        >
            <Panel header={__('Inspector')}>
                <Slot bubblesVirtually />
            </Panel>
        </div>
    );
};

Sidebar.Fill = Fill

export default Sidebar
