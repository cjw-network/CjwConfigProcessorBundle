document.addEventListener("DOMContentLoaded", main);

function main() {
  setUpFundamentalElements();

  if (
    document.querySelector(".param_list") ||
    document.querySelector(".compare_display")
  ) {
    if (!document.querySelector("[favourite]")) {
      setUpFavourites();
    }

    setUpParameterDisplays();
    setUpDownloadAndSyncScrollerButtons();
    setUpFavourAndCopyButtons();
    handleLoadingDisplay();
  }

  if (
    document.querySelector(".cjw_site_access_selectors") &&
    !document.querySelector("[list=favourites]")
  ) {
    handleSplitView();
  }
}

function setUpFundamentalElements() {
  let searchBarUtility = new SearchBarUtility();
  let fundamentalButtonsUtility = new HelpAndScrollUpButtonDisplay();

  searchBarUtility.setUpSearchBar();
  fundamentalButtonsUtility.setUpScrollUpButton();
  fundamentalButtonsUtility.setUpHelp();
}

function setUpParameterDisplays() {
  let parameterDisplay = new ParameterDisplay();
  let parameterLocationRetriever = new ParameterLocationRetrieval();

  let paramBranchDisplay = new ParameterBranchDisplay(
    document.querySelectorAll(".open_subtree")
  );

  parameterDisplay.cleanUpList();
  parameterLocationRetriever.setUpLocationRetrievalButtons();
  paramBranchDisplay.subTreeOpenClickListener();
  paramBranchDisplay.globalSubTreeOpenListener();
}

function setUpDownloadAndSyncScrollerButtons() {
  if (!document.querySelector(".compare_display")) {
    let downloadParametersUtility = new DownloadParametersUtility();
    downloadParametersUtility.setUpDownloadButton();
  } else {
    let synchronousScroller = new SynchronousScrollerUtility();
    synchronousScroller.setUpSynchronousScrollButton();
  }
}

function setUpFavourAndCopyButtons() {
  let copyButtonUtility = new CopyButtonUtility();
  let favourButtonUtility = new FavourButtonUtility();

  copyButtonUtility.setUpCopyButtons();
  favourButtonUtility.setUpFavourButtons();
}

function setUpFavourites() {
  if (!document.querySelector("[favourite]")) {
    let favouriteParameterUtility = new FavouritesHandlingUtility();

    favouriteParameterUtility.setUpFavourites();

    if (document.getElementById("favourites_site_access_selection")) {
      favouriteParameterUtility.setUpSiteAccessSwitching();
    }
  }
}

function handleLoadingDisplay() {
  const loadingCircle = document.querySelector("#loading_circle");

  if (loadingCircle) {
    setTimeout(() => {
      const containerWithLoader = loadingCircle.parentElement;

      if (document.querySelector("#loading_circle")) {
        containerWithLoader?.removeChild(loadingCircle);
      }
    }, 250);
  }
}

function handleSplitView() {
  let saListSplitView = new SAListSplitScreen();

  if (document.querySelector(".param_list")) {
    saListSplitView.disableRightSideBarButtons();
    saListSplitView.setUpSiteAccessSelectionForSingleView();
  }

  if (
    document.querySelector(".first_list") &&
    document.querySelector(".second_list")
  ) {
    saListSplitView.enableRightSideBarButtons();
    saListSplitView.setUpSiteAccessSelectionForCompareView();

    handleComparisonView();
  }
}

function handleComparisonView() {
  const siteAccessComparisonUtility = new SiteAccessComparisonUtility();
  const differenceHighlighter = new SiteAccessDifferencesHighlighter();

  siteAccessComparisonUtility.setUpTheUtilityButtons();
  differenceHighlighter.setUpHighlighterButton();
}
