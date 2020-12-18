<?php


namespace CJW\CJWConfigProcessor\src\Utility;


use Exception;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\Cache\Adapter\AdapterInterface;

/**
 * Class Utility is, as the name implies, a class which is responsible for delivering utility functionality that can
 * be employed (mostly without any conditions) in other classes.
 *
 * @package CJW\CJWConfigProcessor\src\Utility
 */
class Utility
{

    /**
     * Responsible for removing uncommon parameters between two given hierarchical, associative arrays of parameters.
     * It is designed to work with site access versions of the parameters, which means that the parameters typically
     * don't go deeper then two levels.
     *
     * @param array $firstParameterList The first list of parameters to check against the second.
     * @param array $secondParameterList The second list of parameters to check against the first.
     * @param int $level The (optional) amount of levels the comparison has gone to in the two arrays.
     *
     * @return array[] A multi dimensional array, which contains the first and second parameter lists with only the common parameters.
     *
     * @see removeCommonParameters For a similar function which does the opposite.
     */
    public static function removeUncommonParameters (array $firstParameterList, array $secondParameterList, $level = 0)
    {
        $firstListKeys = array_keys($firstParameterList);
        $secondListKeys = array_keys($secondParameterList);

        foreach (array_diff($firstListKeys,$secondListKeys) as $uncommonKey) {
            unset($firstParameterList[$uncommonKey]);
        }

        foreach (array_diff($secondListKeys,$firstListKeys) as $uncommonKey) {
            unset($secondParameterList[$uncommonKey]);
        }

        foreach (array_keys($firstParameterList) as $commonKey) {
            if (
                is_array($firstParameterList[$commonKey]) &&
                is_array($secondParameterList[$commonKey]) &&
                self::has_string_keys($firstParameterList[$commonKey]) &&
                self::has_string_keys($secondParameterList[$commonKey]) &&
                $level < 2
            ) {
                $commonSubKeys =
                    self::removeUncommonParameters(
                        $firstParameterList[$commonKey],
                        $secondParameterList[$commonKey],
                        1+$level
                    );

                $firstParameterList[$commonKey] = $commonSubKeys[0];
                $secondParameterList[$commonKey] = $commonSubKeys[1];
            }
        }

        return [$firstParameterList,$secondParameterList];
    }

    /**
     * Responsible for removing common parameters between two given hierarchical, associative arrays of parameters.
     * It is designed to work with site access versions of the parameters, which means that the parameters typically
     * don't go deeper then two levels.
     *
     * @param array $firstParameterList The first list of parameters to check against the second.
     * @param array $secondParameterList The second list of parameters to check against the first.
     * @param int $level The (optional) amount of levels the comparison has gone to in the two arrays.
     *
     * @return array[] A multi dimensional array, which contains the first and second parameter lists with only the uncommon parameters.
     *
     * @see removeUncommonParameters For a similar function which does the opposite.
     */
    public static function removeCommonParameters (array $firstParameterList, array $secondParameterList, $level = 0)
    {
        $firstListKeys = array_keys($firstParameterList);
        $secondListKeys = array_keys($secondParameterList);

        foreach (array_intersect($firstListKeys,$secondListKeys) as $key) {
            if ($level < 2) {
                $results[0] = $firstParameterList[$key];
                $results[1] = $secondParameterList[$key];

                if (is_array($results[0]) && is_array($results[1])) {
                    $results =
                        self::removeCommonParameters(
                            $firstParameterList[$key],
                            $secondParameterList[$key],
                            1 + $level
                        );
                }

                if ($results[0] === $results[1]) {
                    unset($firstParameterList[$key]);
                    unset($secondParameterList[$key]);
                } else {
                    $firstParameterList[$key] = $results[0];
                    $secondParameterList[$key] = $results[1];
                }
            } else {
                unset($firstParameterList[$key]);
                unset($secondParameterList[$key]);
            }
        }
        return [$firstParameterList, $secondParameterList];
    }

    /**
     * Taken off StackOverflow from
     *
     * @author Captain kurO
     * @url https://stackoverflow.com/questions/173400/how-to-check-if-php-array-is-associative-or-sequential/4254008#4254008
     *
     * @param array $array
     *
     * @return bool
     */
    public static function has_string_keys(array $array)
    {
        return count(
                array_filter(
                    array_keys($array),
                    'is_string'
                )
            ) > 0;
    }

    /**
     * Determines and then returns the defined "pure" site accesses of your installation through a given list of the
     * application configuration. Pure in this case means, that site access groups are not included in that list.
     *
     * @param array $processedParameterArray An associative, hierarchical array of parameters to search for the site accesses.
     *
     * @return string[] Returns an array of site accesses in the form of strings.
     */
    public static function determinePureSiteAccesses(array $processedParameterArray)
    {
        try {
            $results =
                $processedParameterArray["ezpublish"]["siteaccess"]["list"]["parameter_value"];
            array_push($results, "default", "global");

            return $results;
        } catch (Exception $error) {
            return ["default", "global"];
        }
    }

    /**
     * Determines and then returns the defined site access groups of your installation through a given list of the
     * application configuration. Pure in this case means, that the site accesses are not included in that list.
     *
     * @param array $processedParameterArray An associative, hierarchical array of parameters to search for the site accesses.
     *
     * @return array Returns an array of the found site access groups (empty if non are found).
     */
    public static function determinePureSiteAccessGroups (array $processedParameterArray)
    {
        try {
            return $processedParameterArray["ezpublish"]["siteaccess"]["groups"]["parameter_value"];
        } catch (Exception $error) {
            return [];
        }
    }

    /**
     * Takes a given list of hierarchical key segments and also an associative array of parameters and aims to delete
     * the key at the very bottom of the key list from the parameters array.
     *
     * <br>For example: Giving only one key segment / key in the list will remove that key from the very first level of
     * the associative parameters array.
     *
     * @param array $parameters An associative array of parameters from which to delete the given key.
     * @param array $keyList A list of keys that will be gone through to the very last given segment, which is then going to be deleted from the given parameters array.
     *
     * @return array Returns the remaining array of parameters, after the key segment has been deleted.
     */
    public static function removeEntryThroughKeyList (array $parameters, array $keyList)
    {
        $key = reset($keyList);
        array_splice($keyList,0,1);

        if (key_exists($key,$parameters)) {
            $length = count($keyList);

            if ($length > 0) {
                $parameters[$key] = self::removeEntryThroughKeyList($parameters[$key],$keyList);

                if (count($parameters[$key]) === 0) {
                    unset($parameters[$key]);
                }
            } else if ($length === 0) {
                unset($parameters[$key]);
            }
        }

        return $parameters;
    }

    /**
     * Similar to {@see removeEntryThroughKeyList}, aims to remove a specific key from an associative array of parameters.
     * In contrast to the above mentioned function, this one takes only one specific key and goes through the entire
     * array until the key is found, while the other function goes through the given list of segments and only these
     * segments and then deletes the key if it exists.
     *
     * @param string $keySegment The specific key to be removed from the given array.
     * @param array $parametersToRemoveFrom An associative array of parameters from which to remove the given key, if it exists.
     *
     * @return array Returns the resulting array of parameters, after the key segment has been deleted (unchanged from the given array, if the key could not be found).
     */
    public static function removeSpecificKeySegment ($keySegment, array $parametersToRemoveFrom)
    {
        $result = $parametersToRemoveFrom;

        foreach ($parametersToRemoveFrom as $key => $value) {
            if ($key === $keySegment) {
                unset($result[$key]);
            } else if (is_array($value)) {
                $result[$key] =
                    self::removeSpecificKeySegment($keySegment,$result[$key]);
            }
        }

        return $result;
    }

    /**
     * Since Symfony 3.4 does not feature cache contracts, but instead only PSR-6-cache but the original
     * functionality was built with cache contracts in mind, this is a replacement function for the cache
     * contracts in Symfony 5 and above.
     *
     * @param string $cacheKey The key for the item that is supposed to be retrieved.
     * @param AdapterInterface $cachePool The cache adapter used to store the item.
     * @param callable $executeWhenItemNotSet A function to call, when the item could not be found in the cache.
     * @param false $enforceSet Since the cache did not detect properly that some items had been deleted, here a boolean to enforce the item to be set to the given value.
     *
     * @return mixed Returns the cached value
     *
     * @throws InvalidArgumentException
     */
    public static function cacheContractGetOrSet ($cacheKey, AdapterInterface $cachePool, callable $executeWhenItemNotSet, $enforceSet = false)
    {
        if (!$cachePool->hasItem($cacheKey) || $enforceSet) {
            $item = $cachePool->getItem($cacheKey);
            $item->set($executeWhenItemNotSet());

            $cachePool->save($item);
        }

        return $cachePool->getItem($cacheKey)->get();
    }
}
