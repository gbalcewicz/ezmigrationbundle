<?php
/**
 * Created by PhpStorm.
 * User: daniel
 * Date: 11/12/15
 * Time: 15:04
 */

namespace Kaliop\eZMigrationBundle\Core\API\ComplexFields;


class EzXmlText extends AbstractComplexField
{
    /**
     * Replace any references in an xml string to be used as the input data for an ezxmltext field.
     *
     * @return string
     */
    public function createValue()
    {
        $xmlText = $this->fieldValueArray['content'];

        //Check if there are any references in the xml text and replace them.
        // $result[0][] will have the matched full string eg.: [reference:example_reference]
        // $result[1][] will have the reference id eg.: example_reference
        $count = preg_match_all('|\[reference:([^\]\[]*)\]|', $xmlText, $result);

        if ($count !== false and count($result) > 1) {
            foreach ($result[1] as $index => $referenceIdentifier) {
                $reference = $this->contentManager->getReference($referenceIdentifier);

                $xmlText = str_replace($result[0][$index], $reference, $xmlText);
            }
        }

        return $xmlText;
    }
}