var forms = [];
var updatedForms = [];

function setWarningOnNavigate() {
    forms = document.getElementsByTagName("form");
    for (var i = 0, formsLen = forms.length; i < formsLen; i++) {
        var form = forms[i];
        updatedForms[i] = false;
        var fields = form.querySelectorAll('input[type="text"],textarea');
        for (var j = 0, fieldsLen = fields.length; j < fieldsLen; j++) {
            var field = fields[j];
            field.addEventListener('input', function (i) {
                updatedForms[i] = true;
            }).bind(i);
        }
        form.onsubmit = function () {
            for (var j = 0, formsLen = updatedForms.length; j < formsLen; j++) {
                var formUpdated = updatedForms[j];
                if (formUpdated && j != i)
                    return confirm('You have not yet saved your previous changes. You will lose them if you proceed with the current action. Do you wish to proceed?');
            }
            return true;
        };
    }
    links = document.getElementsByTagName("a");
    for (var i = 0, formsLen = forms.length; i < formsLen; i++) {
        var link = links[i];
        link.addEventListener('click', function (e) {
            for (var j = 0, formsLen = updatedForms.length; j < formsLen; j++) {
                var formUpdated = updatedForms[j];
                if (formUpdated) {
                    if (!confirm('You have not yet saved your previous changes. You will lose them if you proceed with the current action. Do you wish to proceed?')) {
                        e.preventDefault();
                        e.stopPropagation();
                        return false;
                    } else
                        return true;
                }
            }
            return true;
        });
    }
}
