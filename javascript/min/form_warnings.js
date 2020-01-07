var forms = [];
var updatedForms = [];

function setWarningOnNavigate() {
    forms = document.getElementsByTagName("form");
    for (var i = 0, formsLen = forms.length; i < formsLen; i++) {
        var form = forms[i];
        var formName = form.name;
        updatedForms[formName] = false;
        var fields = form.querySelectorAll('input[type="text"],textarea');
        for (var j = 0, fieldsLen = fields.length; j < fieldsLen; j++) {
            var field = fields[j];
            field.addEventListener('input', function () {
                updatedForms[formName] = true;
            });
        }
        form.onsubmit = function () {
            if (updatedForms[formName]) {
                return confirm('You are going to lose your changes. Do you wish to proceed?');
            }
            return true;
        };
    }
}
