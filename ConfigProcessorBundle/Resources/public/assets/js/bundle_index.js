document.addEventListener("DOMContentLoaded", function () {
  let searchBarUtility = new SearchBarUtility();
  let scrollUpButtonDisplay = new ScrollUpButtonDisplay();

  searchBarUtility.setUpSearchBar();
  scrollUpButtonDisplay.setUpScrollUpButton();
});
