<?php
namespace Smichaelsen\SocialGrabber\Eid;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Smichaelsen\SocialGrabber\Service\Instagram\AccessTokenService;
use Smichaelsen\SocialGrabber\Service\Instagram\InstagramApiClient;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class InstagramOAuth
{

    public function processRequest(ServerRequestInterface $request, ResponseInterface $response)
    {
        $requestToken = $request->getQueryParams()['requestToken'];
        try {
            if ($requestToken !== self::getRequestToken()) {
                $response->withStatus(401);
                $response->getBody()->write('invalid request token');
                return $response;
            }
        } catch (\Exception $e) {
            $response->withStatus(401);
            $response->getBody()->write('invalid request token');
            return $response;
        }
        $code = $request->getQueryParams()['code'];
        $data = GeneralUtility::makeInstance(InstagramApiClient::class)->getOAuthToken($code);
        GeneralUtility::makeInstance(AccessTokenService::class)->setAccessToken($data->access_token);
        $response->getBody()->write('Authentication successful. You may close this window.');
        return $response;
    }

    /**
     * @return string
     * @throws \Exception
     */
    public static function getRequestToken()
    {
        if (empty($GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'])) {
            throw new \Exception('Empty TYPO3 encryption key', 1513693252);
        }
        /** @var BackendUserAuthentication $beUser */
        $beUser = $GLOBALS['BE_USER'];
        if (!$beUser instanceof BackendUserAuthentication) {
            Bootstrap::getInstance()->initializeBackendUser();
            $beUser = $GLOBALS['BE_USER'];
        }
        return hash('sha256', $beUser->user['uid'] . $GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey']);
    }

}
