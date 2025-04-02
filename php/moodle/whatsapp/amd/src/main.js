define([], function() {
    return {
        init: function() {
            setTimeout(function() {
                var alertElement = document.querySelector('.alert');
                if (alertElement) {
                    alertElement.style.transition = "opacity 0.5s ease";
                    alertElement.style.opacity = 0;
                    setTimeout(function() {
                        if (alertElement.parentNode) {
                            alertElement.parentNode.removeChild(alertElement);
                        }
                    }, 500);
                }
            }, 5000);
        }
    };
});
