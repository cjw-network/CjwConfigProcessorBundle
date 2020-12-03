<?php


namespace CJW\CJWConfigProcessor\src\Utility;


use Exception;

class Utility
{

    public static function removeUncommonParameters (
        array $firstParameterList,
        array $secondParameterList,
        int $level = 0
    ) {
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

    public static function removeCommonParameters (
        array $firstParameterList,
        array $secondParameterList,
        int $level = 0
    ) {
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
     * @author Captain kurO
     * @url https://stackoverflow.com/questions/173400/how-to-check-if-php-array-is-associative-or-sequential/4254008#4254008
     * @param array $array
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

    public static function determinePureSiteAccesses(
        array $processedParameterArray
    ): array {
        try {
            $results =
                $processedParameterArray["ezpublish"]["siteaccess"]["list"]["parameter_value"];
            array_push($results, "default", "global");

            return $results;
        } catch (Exception $error) {
            return ["default", "global"];
        }
    }

    public static function determinePureSiteAccessGroups (
        array $processedParameterArray
    ): array {
        try {
            return $processedParameterArray["ezpublish"]["siteaccess"]["groups"]["parameter_value"];
        } catch (Exception $error) {
            return [];
        }
    }

    public static function removeEntryThroughKeyList (
        array $parameters,
        array $keyList
    ): array {
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

    public static function removeSpecificKeySegment (
        string $keySegment,
        array $parametersToRemoveFrom
    ) {
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
}
