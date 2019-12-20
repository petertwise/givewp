import './style.scss'
const { __ } = wp.i18n

const PeriodSelector = ({date, range, onChange}) => {

    const icon = <svg width="18" height="18" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg">
        <g opacity="0.501465">
        <path opacity="0.3" d="M3.75 6H14.25V4.5H3.75V6Z" fill="black"/>
        <path fill-rule="evenodd" clip-rule="evenodd" d="M13.5 3H14.25C15.075 3 15.75 3.675 15.75 4.5V15C15.75 15.825 15.075 16.5 14.25 16.5H3.75C2.9175 16.5 2.25 15.825 2.25 15L2.2575 4.5C2.2575 3.675 2.9175 3 3.75 3H4.5V1.5H6V3H12V1.5H13.5V3ZM6.75 9.75V8.25H5.25V9.75H6.75ZM14.25 15H3.75V7.5H14.25V15ZM3.75 6H14.25V4.5H3.75V6ZM12.75 8.25V9.75H11.25V8.25H12.75ZM9.75 8.25H8.25V9.75H9.75V8.25Z" fill="black"/>
        </g>
    </svg>
    
    return (
        <div className='givewp-period-selector'>
            <button className='icon'>{icon}</button>
            <div className='group'>
                <button>{__('Day', 'give')}</button>
                <button className='selected'>{__('Week', 'give')}</button>
                <button>{__('Month', 'give')}</button>
                <button>{__('Year', 'give')}</button>
            </div>
        </div>
    )
}

export default PeriodSelector