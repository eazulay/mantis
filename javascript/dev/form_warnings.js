var forms = [];
var updatedForms = [];

function setWarningOnNavigate() {
    forms = document.getElementsByTagName("form");
    for (var formKey in forms) {
        var form = forms[formKey];
        var formName = form.name;
        updatedForms[formName] = false;
        var fields = form.querySelectorAll('input[type="text"],textarea');
        if (fields.length > 0) {
            for (var fieldKey in fields) {
                var field = fields[fieldKey];
                field.addEventListener('input', function () {
                    updatedForms[formName] = true;
                });
            }
        }
        form.addEventListener('submit', function () {
            if (updatedForms[formName]) {
                return confirm('You are going to lose your changes. Do you wish to proceed?');
            }
            return true;
        });
    }
}
