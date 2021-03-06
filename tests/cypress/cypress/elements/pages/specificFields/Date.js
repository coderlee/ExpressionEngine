import ControlPanel from '../ControlPanel'

class DateC extends ControlPanel {
    constructor() {
            super()
            
            this.elements({
               //Constants for all Date only has these
                "Save": 'button[type="submit"][value="save"]',
                "Name" : 'input[type="text"][name = "field_label"]',
                "Instructions" : 'textarea[name = "field_instructions"]',
                "Required" : 'button[data-toggle-for = "field_required"]',
                "Search" : 'button[data-toggle-for = "field_search"]',
                "Hidden" : 'button[data-toggle-for = "field_is_hidden"]',
            })
        }
}
export default DateC;