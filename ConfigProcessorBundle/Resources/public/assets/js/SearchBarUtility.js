class SearchBarUtility {

    header;
    mainSection;

    constructor() {
        this.header = document.querySelector(".cjw_header");
        this.mainSection = document.querySelector(".cjw_main_section");
    }

    setUpSearchBar() {
        if (this.header) {
            // const searchForm = document.createElement("form");
            // const searchField = document.createElement("input");
            // searchField.type="text";
            //
            // searchForm.classList.add("searchbar");
            // searchForm.appendChild(searchField);

            const searchField = document.querySelector(".searchbar > input");

            searchField.addEventListener("input", (event) => {
                event.preventDefault();
                this.reactToSearchInput(event.target.value);
            })

            // this.header.appendChild(searchForm);
        }
    }

    reactToSearchInput(searchText) {
        if (searchText.trim().length === 0) {
            this.resetList();
            return;
        }

        if (this.mainSection) {
            // const highlightedNode = document.querySelector(".first_search_result");
            // if (highlightedNode) {
            //     highlightedNode.classList.toggle("first_search_result");
            // }

            const highlightedNodes = document.querySelectorAll(".first_search_result");
            for (const highlightedNode of highlightedNodes) {
                highlightedNode.classList.remove("first_search_result");
            }

            this.removeRemainingIrrelevantResults(searchText);

            if (searchText.trim().length > 0) {
                // const possibleResults = this.mainSection.querySelectorAll(`[key^="${searchText}" i]`);
                const possibleResults = this.mainSection.querySelectorAll(`[key*="${searchText}" i]`);

                if (possibleResults.length > 0) {
                    possibleResults[0].scrollIntoView();
                    // possibleResults[0].classList.toggle("first_search_result");
                }

                for (const result of possibleResults) {
                    this.createNodeListToRoot(result);
                    result.classList.add("first_search_result");
                }
            }
        }
    }

    createNodeListToRoot (node) {
        // const nodeList = [];

        // if (node.classList.contains("dont_display")) {
        if (node.offsetParent === null || node.classList.contains("dont_display")) {
            const nodeParent = node.parentElement;

            if (nodeParent && !nodeParent.classList.contains("param_list")) {
                if (nodeParent.previousElementSibling) {
                    nodeParent.previousElementSibling.classList.remove("dont_display");
                }

                // nodeList.push(this.createNodeListToRoot(node.parent));
                this.createNodeListToRoot(nodeParent);
            }

            node.classList.remove("dont_display");
        }

        // return nodeList;
    }

    removeRemainingIrrelevantResults (searchText) {
        // const nonRelevantVisibleResults = this.mainSection.querySelectorAll(`div:not(.dont_display):not([key^="${searchText}" i]), [key]`)
        let nonRelevantVisibleResults = this.mainSection.querySelectorAll(`div:not(.dont_display):not([key*="${searchText}" i]), [key]`)
        nonRelevantVisibleResults = Array.from(nonRelevantVisibleResults).filter((node) => node.classList.contains("param_list_items"));

        for (const nonRelevantResult of nonRelevantVisibleResults) {
            nonRelevantResult.classList.add("dont_display");
        }
    }

    resetList() {
        this.removeRemainingIrrelevantResults("");
        const rootNodes = document.querySelectorAll(".param_list > .param_list_items");

        for (const node of rootNodes) {
            node.classList.remove("dont_display");

            for (const childNode of node.children) {
                if (childNode.classList.contains("param_list_keys")) {
                    childNode.classList.remove("dont_display");
                }
            }
        }

        // const lastResult = document.querySelector(".first_search_result");
        //
        // if (lastResult) {
        //     lastResult.classList.remove("first_search_result")
        // }

        const lastResults = document.querySelectorAll(".first_search_result");

        for (const lastResult of lastResults) {
            lastResult.classList.remove("first_search_result")
        }
    }
}
