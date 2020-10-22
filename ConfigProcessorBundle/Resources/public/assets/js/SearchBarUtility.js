class SearchBarUtility {

    header;
    mainSection;

    constructor() {
        this.header = document.querySelector(".cjw_header");
        this.mainSection = document.querySelector(".cjw_main_section");
    }

    setUpSearchBar() {
        if (this.header) {
            const searchField = document.querySelector(".searchbar > input");
            let timeout = null;

            searchField.addEventListener("input", (event) => {
                event.preventDefault();

                clearTimeout(timeout);

                timeout = setTimeout(() => {
                    searchField.disabled = true;
                    this.reactToSearchInput(event.target.value).then(() => {
                        searchField.disabled = false;
                    });
                }, 750);
            })
        }
    }

    async reactToSearchInput(originalQueryText) {
        const queryText = originalQueryText.trim();

        if (queryText.length === 0 ) {
            await this.resetList();
            return;
        }

        if (new RegExp(/^[.:]/).test(queryText) || new RegExp(/[.:]$/).test(queryText)) {
            return;
        }

        if (this.mainSection) {
            this.removeNodeHighlightings();

            const keys = queryText.split(/[.:]/);
            let searchText = queryText;
            let searchPool = this.mainSection;

            if (keys && keys.length > 1) {
                searchText = keys.splice(keys.length - 1, 1)[0];
                searchPool = this.lookForKeyHierachie(keys);
            }

            await this.removeRemainingIrrelevantResults(searchText);

            // const possibleResults = this.mainSection.querySelectorAll(`[key*="${searchText.trim()}" i]`);
            const possibleResults = this.conductSearch(searchPool, searchText);

            if (possibleResults && possibleResults.length > 0) {
                possibleResults[0].scrollIntoView();
            }

            await this.createNodeListToRootAsynchronously(0, possibleResults);
        }
    }

    removeNodeHighlightings() {
        const highlightedNodes = document.querySelectorAll(".first_search_result");

        for (const highlightedNode of highlightedNodes) {
            highlightedNode.classList.remove("first_search_result");
        }
    }

    lookForKeyHierachie(keys) {
        let results = [];

        for (const key of keys) {
            let temporaryResults = [];

            for (const result of results) {
                const temporaryCarrier = result.querySelectorAll(`[key="${key.trim()}" i]`);

                if (temporaryCarrier && temporaryCarrier.length > 0) {
                    temporaryResults.push(...temporaryCarrier);
                }
            }

            if (temporaryResults.length === 0) {
                const temporaryCarrier = document.querySelectorAll(`.top_nodes[key="${key.trim()}" i]`);

                if (temporaryCarrier && temporaryCarrier.length > 0) {
                    temporaryResults.push(...temporaryCarrier);
                }
            }

            results = [];

            for (const foundKey of temporaryResults) {

                const nextSearchNode = (foundKey.parentElement && foundKey.parentElement.children) ?
                    Array.from(foundKey.parentElement.children)
                        .filter((node) => node.classList.contains("param_list_items"))
                    : null;

                if (nextSearchNode) {
                    results.push(...nextSearchNode);
                }
            }
        }

        return results;
    }

    async removeRemainingIrrelevantResults (searchText) {
        let nonRelevantVisibleResults = this.mainSection.querySelectorAll(`div:not(.dont_display):not([key*="${searchText}" i]), [key]`)
        nonRelevantVisibleResults = Array.from(nonRelevantVisibleResults).filter((node) => node.classList.contains("param_list_items"));

        for (const nonRelevantResult of nonRelevantVisibleResults) {
            nonRelevantResult.classList.add("dont_display");
        }

        // await this.removeNodesAsynchronously(0,nonRelevantVisibleResults);
    }

    conductSearch(searchPool, searchText) {
        const possibleResults = [];

        if (searchPool === this.mainSection) {
            const temporaryResultCarrier = searchPool.querySelectorAll(`[key*="${searchText.trim()}" i]`);

            if (temporaryResultCarrier) {
                possibleResults.push(...temporaryResultCarrier);
            }

        } else {
            for (const pool of searchPool) {
                const temporaryResultCarrier = pool.querySelectorAll(`[key*="${searchText.trim()}" i]`);

                if (temporaryResultCarrier) {
                    possibleResults.push(...temporaryResultCarrier);
                }
            }
        }

        return possibleResults;
    }

    async createNodeListToRootAsynchronously(counter, nodeList) {
        if (nodeList && nodeList.length > counter >= 0) {
            do {
                const result = nodeList[counter];

                if (result) {
                    this.createNodeListToRoot(result);
                    result.classList.add("first_search_result");
                }

                ++counter;
            } while (counter < nodeList.length && (counter % 40 !== 0))

            if (counter < nodeList.length) {
                await setTimeout(() => {
                    this.createNodeListToRootAsynchronously(counter, nodeList);
                });
            }
        }
    }

    createNodeListToRoot (node) {

        if (node.offsetParent === null || node.classList.contains("dont_display")) {
            const nodeParent = node.parentElement;

            if (nodeParent && !nodeParent.classList.contains("param_list")) {
                this.createNodeListToRoot(nodeParent);
            }

            node.classList.remove("dont_display");
        }
    }

    // async removeNodesAsynchronously(counter, nodeList) {
    //     if (nodeList && nodeList.length > counter >= 0) {
    //         do {
    //             if (nodeList[counter]) {
    //                 nodeList[counter].classList.add("dont_display");
    //             }
    //             ++counter;
    //
    //         } while (counter < nodeList.length && (counter % 40 !== 0))
    //
    //
    //         await setTimeout(() => {
    //             this.removeNodesAsynchronously(counter, nodeList);
    //         })
    //
    //
    //     }
    // }


    async resetList() {
        await this.removeRemainingIrrelevantResults("");
        const rootNodes = document.querySelectorAll(".param_list > .param_list_items");

        for (const node of rootNodes) {
            node.classList.remove("dont_display");

            for (const childNode of node.children) {
                if (childNode.classList.contains("param_list_keys")) {
                    childNode.classList.remove("dont_display");
                }
            }
        }

        const lastResults = document.querySelectorAll(".first_search_result");

        for (const lastResult of lastResults) {
            lastResult.classList.remove("first_search_result")
        }
    }
}
