class SiteAccessDifferencesHighlighter {

    differenceHighlightingButton;

    constructor () {
        this.differenceHighlightingButton = document.querySelector("#cjw_highlight_differences");
    }

    setUpFunctionality () {
        if (this.differenceHighlightingButton) {
            this.differenceHighlightingButton.onclick = (event) => {
                event.preventDefault();
                event.stopPropagation();

                this.differenceHighlightingButton.classList.add("switch");
            }
        }
    }

    findDifferences () {
        let firstListKeys = document.querySelectorAll(".first_list > .param_list_keys");
        let secondListKeys = document.querySelectorAll(".second_list > .param_list_keys");

        if (firstListKeys && secondListKeys) {
            firstListKeys = Array.from(firstListKeys);
            secondListKeys = Array.from(secondListKeys);

            this.findDifferencesInKeysAsynchronously(firstListKeys, secondListKeys);
        }

    }

    findDifferencesInKeysAsynchronously (firstList, secondList) {
        
    }
}
