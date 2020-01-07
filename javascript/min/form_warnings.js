var forms = [];
var updatedForms = [];

function setFormUpdated(formIdx, fieldName) {
    return function () {
        if (!updatedForms[formIdx].includes(fieldName))
            updatedForms[formIdx].push(fieldName);
    }
}

function confirmNotSavingChanges(formIdx) {
    return function (e) {
        var updatedFields = [];
        for (var i = 0, formsLen = updatedForms.length; i < formsLen; i++) {
            var formUpdated = (updatedForms[i].length > 0);
            if (formUpdated && i != formIdx)
                updatedFields = updatedFields.concat(updatedForms[i]);
        }
        if (updatedFields.length > 0) {
            if (!confirm('You have not yet saved your changes to ' +
                updatedForms[i].join(', ') +
                '. You will lose them if you proceed with the current action. Do you wish to proceed?')) {
                e.preventDefault();
                e.stopPropagation();
                return false;
            } else
                return true;
        }
        return true;
    }
}

function setWarningOnNavigate() {
    forms = document.getElementsByTagName("form");
    for (var i = 0, formsLen = forms.length; i < formsLen; i++) {
        var form = forms[i];
        updatedForms[i] = [];
        var fields = form.querySelectorAll('input[type="text"],textarea');
        for (var j = 0, fieldsLen = fields.length; j < fieldsLen; j++) {
            var field = fields[j];
            field.addEventListener('input', setFormUpdated(i, field.name));
        }
        form.addEventListener('submit', confirmNotSavingChanges(i));
    }
    links = document.getElementsByTagName("a");
    for (var i = 0, formsLen = forms.length; i < formsLen; i++) {
        var link = links[i];
        link.addEventListener('click', confirmNotSavingChanges(-1));
    }
}
