class SiteAccessComparisonUtility {
    commonParamSelectButton;
    uncommonParamSelectButton;

    constructor() {
        this.commonParamSelectButton = document.querySelector("#cjw_show_common_parameters");
        this.uncommonParamSelectButton = document.querySelector("#cjw_show_uncommon_parameters");
    }

    setUpBothUtilityButtons() {
        if (this.commonParamSelectButton) {
            this.commonParamSelectButton.onclick = (event) => {
                event.preventDefault();
                event.stopPropagation();

                this.commonParamSelectButton.classList.add("switch");
                this.redirectToLimitedView("commons");
            }
        }

        if (this.uncommonParamSelectButton) {
            this.uncommonParamSelectButton.onclick = (event) => {
                event.preventDefault();
                event.stopPropagation();

                this.uncommonParamSelectButton.classList.add("switch");
                this.redirectToLimitedView("uncommons");
            }
        }
    }

    // getAllCommonParameters() {
    //     const firstListParameters = document.querySelectorAll(".first_list > .param_list_keys");
    //
    //     for (const key of firstListParameters) {
    //         const pendantElement = document.querySelector(".second_list")
    //     }
    // }

    redirectToLimitedView (limiterKeyWord) {
        if (limiterKeyWord && (limiterKeyWord === "commons" || limiterKeyWord === "uncommons")) {
            let firstSiteAccess = document.querySelector(".first_list");
            let secondSiteAccess = document.querySelector(".second_list");

            firstSiteAccess = firstSiteAccess? firstSiteAccess.getAttribute("siteaccess") : "";
            secondSiteAccess = secondSiteAccess? secondSiteAccess.getAttribute("siteaccess") : "";

            window.location = `/admin/cjw/config-processing/parameter_list/compare/${firstSiteAccess}/${secondSiteAccess}/${limiterKeyWord}`;
        }
    }
}
