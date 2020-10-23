document.addEventListener("DOMContentLoaded", () => {
    if (document.querySelector("#cjw_split_selection")) {
        const saListSplitView = new SAListSplitScreen();
        saListSplitView.setUpSplitViewButton();
    }

    if (document.querySelector(".first_list") && document.querySelector(".second_list")) {
        const siteAccessComparisonUtility = new SiteAccessComparisonUtility();
        siteAccessComparisonUtility.setUpBothUtilityButtons();
    }
})
