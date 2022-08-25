import { ToolbarButton, createSlotFill } from '@wordpress/components'
import { createElement } from '@wordpress/element'
import { cog as cogIcon } from '@wordpress/icons'
import {__} from "@wordpress/i18n";

const { Slot, Fill } = createSlotFill(
    'HeaderToolbar'
);

interface HeaderProps {
    toggleSidebar: () => void,
    sidebarOpen: boolean
}

const Header = ({ toggleSidebar, sidebarOpen }: HeaderProps) => {
    return (
        <div
            className="block-editor__header"
            role="region"
        >
            <Slot className="block-editor__header-toolbar"  bubblesVirtually />
            <ToolbarButton onClick={toggleSidebar} isPressed={sidebarOpen} icon={cogIcon} label={__('Settings')} />
        </div>
    );
};

Header.Fill = Fill;

export default Header;
