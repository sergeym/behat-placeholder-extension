<?php
declare(strict_types = 1);

namespace espend\Behat\PlaceholderExtension\Transformer;

use Behat\Behat\Definition\Call\DefinitionCall;
use Behat\Behat\Transformation\Transformer\ArgumentTransformer;
use espend\Behat\PlaceholderExtension\PlaceholderBagInterface;
use espend\Behat\PlaceholderExtension\Utils\PlaceholderUtil;
use Behat\Gherkin\Node\TableNode;

/**
 * @author Daniel Espendiller <daniel@espendiller.net>
 */
class PlaceholderArgumentTransformer implements ArgumentTransformer
{
    /**
     * @var PlaceholderBagInterface
     */
    private $placeholderBag;

    /**
     * @param PlaceholderBagInterface $placeholderBag
     */
    public function __construct(PlaceholderBagInterface $placeholderBag)
    {
        $this->placeholderBag = $placeholderBag;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsDefinitionAndArgument(DefinitionCall $definitionCall, $argumentIndex, $argumentValue)
    {
        if (is_string($argumentValue)) {
            // '%FOO%', '%foo%'
            return $this->isStringContainPlaceholder($argumentValue);
        } elseif ($argumentValue instanceof TableNode) {
            foreach ($argumentValue as $item) {
                if ($this->isStringContainPlaceholder($item['key'])) {
                    return true;
                }
                if ($this->isStringContainPlaceholder($item['value'])) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function transformArgument(DefinitionCall $definitionCall, $argumentIndex, $argumentValue)
    {

        if (is_string($argumentValue)) {
            // '%FOO%', '%foo%'
            $argumentValue = $this->replaceInString($argumentValue);
        } elseif ($argumentValue instanceof TableNode) {
            $table = $argumentValue->getTable();

            foreach ($table as $index => $item) {
                $table[$index][0] = $this->replaceInString($item[0]);
                $table[$index][1] = $this->replaceInString($item[1]);
            }
            $argumentValue = new TableNode($table);
        }

        return $argumentValue;
    }

    /**
     * {@inheritdoc}
     */
    private function isStringContainPlaceholder($argumentValue)
    {
        if (PlaceholderUtil::isValidPlaceholder($argumentValue)) {
            return ($placeholders = $this->placeholderBag->all())
                && isset($placeholders[$argumentValue]);
        }

        // 'foobar%FOO%'
        foreach ($this->placeholderBag->all() as $key => $value) {
            if (false !== strpos($argumentValue, $key)) {
                return true;
            }
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    private function replaceInString($argumentValue)
    {
        // 'foobar%FOO%'
        foreach ($this->placeholderBag->all() as $key => $value) {
            $argumentValue = str_replace($key, $value, $argumentValue);
        }

        // '%FOO%', '%foo%'
        $placeholder = $this->placeholderBag->all();
        if (isset($placeholder[$argumentValue])) {
            return $placeholder[$argumentValue];
        }

        return $argumentValue;
    }
}
