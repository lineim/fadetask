export default [
    {
        name: 'createCustomField',
        url: "/kanban/{kanbanId}/customfield",
        method: "POST",
        disableLoading: true
    },
    {
        name: 'editCustomField',
        url: "/customfield/{id}/edit",
        method: "POST",
        disableLoading: true
    },
    {
        name: 'delCustomField',
        url: "/customfield/{id}",
        method: "DELETE",
        disableLoading: true
    },
    {
        name: 'addFieldOption',
        url: "/customfield/{fieldId}/addOption",
        method: "POST",
        disableLoading: true
    },
    {
        name: 'setFieldOption',
        url: "/customfield/option/{id}",
        method: "POST",
        disableLoading: true
    },
    {
        name: 'delCustomFieldOption',
        url: "/customfield/option/{id}",
        method: "DELETE",
        disableLoading: true
    },
    {
        name: 'setCardFieldVal',
        url: '/task/{cardId}/field/{id}/val',
        method: 'POST',
        disableLoading: true
    }
]