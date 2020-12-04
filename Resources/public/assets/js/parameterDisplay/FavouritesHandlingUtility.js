class FavouritesHandlingUtility {
  dedicatedFavouriteViewContainer;
  favourButtonHandler;
  utility;

  constructor() {
    this.dedicatedFavouriteViewContainer = document.querySelector(
      "[list=favourites]"
    );

    this.utility = new Utility();
    this.favourButtonHandler = new FavourButtonUtility();
  }

  async setUpFavourites() {
    const res = await this.utility.performFetchRequestWithoutBody(
      "/cjw/config-processing/parameter_list/keylist/favourites",
      "GET"
    );

    if (res) {
      const responseKeyList = await res.json();

      const results = this.parseKeyList(responseKeyList);
      this.markKeysAsFavourites(results);
    }
  }

  parseKeyList(responseKeyList) {
    const resultKeys = [];

    if (responseKeyList && responseKeyList instanceof Object) {
      const keyList = Object.keys(responseKeyList);

      for (const key of keyList) {
        if (responseKeyList[key].length === 0) {
          resultKeys.push(key);
        } else if (responseKeyList[key] instanceof Object) {
          const childKeys = this.parseKeyList(responseKeyList[key]);

          for (const childKey of childKeys) {
            resultKeys.push(key + "." + childKey);
          }
        }
      }
    }

    return resultKeys;
  }

  markKeysAsFavourites(keyList) {
    if (keyList) {
      for (const key of keyList) {
        const correspondingNode = document.querySelector(
          '[fullparametername="' + key + '"]'
        );

        if (correspondingNode && correspondingNode.parentElement) {
          const nodeParent = correspondingNode.parentElement;
          nodeParent.setAttribute("favourite", "true");

          let favourButton = null;

          for (const child of nodeParent.children) {
            if (child.classList.contains("favour_parameter")) {
              favourButton = child;
              break;
            }
          }

          this.favourButtonHandler.switchFavourButtonModel(
            favourButton,
            nodeParent
          );
        }
      }
    }
  }

  setUpSiteAccessSwitching() {
    const switcher = document.getElementById(
      "favourites_site_access_selection"
    );

    if (switcher) {
      switcher.onchange = (event) => {
        event.preventDefault();
        event.stopPropagation();

        this.swapSiteAccessView(switcher.value);
      };
    }
  }

  swapSiteAccessView(siteAccess) {
    if (siteAccess && siteAccess.trim().length > 0) {
      if (siteAccess === "- no.siteaccess -") {
        window.location =
          "/admin/cjw/config-processing/parameter_list/favourites";
      } else {
        window.location = `/admin/cjw/config-processing/parameter_list/favourites/${siteAccess}`;
      }
    }
  }
}
