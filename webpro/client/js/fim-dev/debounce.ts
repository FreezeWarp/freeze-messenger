class Debounce {
    timeout;
    invoke(func, wait, immediate) {
        let context = this, args = arguments;
        let later = function() {
            this.timeout = null;
            if (!immediate) func.apply(context, args);
        };
        let callNow = immediate && !this.timeout;
        clearTimeout(this.timeout);
        this.timeout = setTimeout(later, wait);
        if (callNow) func.apply(context, args);
    }
}