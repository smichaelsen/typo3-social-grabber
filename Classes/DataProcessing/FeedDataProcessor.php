<?php

namespace Smichaelsen\SocialGrabber\DataProcessing;

use TYPO3\CMS\Core\Database\DatabaseConnection;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Service\FlexFormService;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\ContentObject\DataProcessorInterface;
use TYPO3\CMS\Frontend\Plugin\AbstractPlugin;

class FeedDataProcessor extends AbstractPlugin implements DataProcessorInterface
{

    /**
     * Process content object data
     *
     * @param ContentObjectRenderer $cObj The data of the content element or page
     * @param array $contentObjectConfiguration The configuration of Content Object
     * @param array $processorConfiguration The configuration of this processor
     * @param array $processedData Key/value store of processed data (e.g. to be passed to a Fluid View)
     * @return array the processed data as key/value store
     */
    public function process(
        ContentObjectRenderer $cObj,
        array $contentObjectConfiguration,
        array $processorConfiguration,
        array $processedData
    ) {
        $channel = (int) $this->getFlexFormValue($processedData['data']['pi_flexform'], 'channel');
        if ($channel > 0) {
            $posts = $this->getDatabaseConnection()->exec_SELECTgetRows(
                '*',
                'tx_socialgrabber_domain_model_post',
                'channel = ' . $channel,
                '',
                'publication_date DESC'
            );
            $processedData['posts'] = $posts;
        }
        return $processedData;
    }

    protected function getFlexFormValue($flexFormContent, $fieldName)
    {
        $data = GeneralUtility::makeInstance(FlexFormService::class)->convertFlexFormContentToArray($flexFormContent);
        return isset($data[$fieldName]) ? $data[$fieldName] : null;
    }

    /**
     * @return DatabaseConnection
     */
    protected function getDatabaseConnection()
    {
        return $GLOBALS['TYPO3_DB'];
    }
}