<?php
namespace TYPO3\CMS\Install\Http;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Configuration\ConfigurationManager;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\DateTimeAspect;
use TYPO3\CMS\Core\Context\UserAspect;
use TYPO3\CMS\Core\Context\VisibilityAspect;
use TYPO3\CMS\Core\Context\WorkspaceAspect;
use TYPO3\CMS\Core\Http\AbstractApplication;
use TYPO3\CMS\Core\Http\MiddlewareDispatcher;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Install\Middleware\Installer;
use TYPO3\CMS\Install\Middleware\Maintenance;

/**
 * Entry point for the TYPO3 Install Tool
 * @internal This class is only meant to be used within EXT:install and is not part of the TYPO3 Core API.
 */
class Application extends AbstractApplication
{
    /**
     * @var string
     */
    protected $requestHandler = NotFoundRequestHandler::class;

    /**
     * @var ConfigurationManager
     */
    protected $configurationManager;

    /**
     * @param ConfigurationManager $configurationManager
     */
    public function __construct(ConfigurationManager $configurationManager)
    {
        $this->configurationManager = $configurationManager;
    }

    /**
     * @param RequestHandlerInterface $requestHandler
     * @return MiddlewareDispatcher
     */
    protected function createMiddlewareDispatcher(RequestHandlerInterface $requestHandler): MiddlewareDispatcher
    {
        $dispatcher = new MiddlewareDispatcher($requestHandler);

        // Stack of middlewares, executed LIFO
        $dispatcher->lazy(Installer::class);
        $dispatcher->add(GeneralUtility::makeInstance(Maintenance::class, $this->configurationManager));

        return $dispatcher;
    }

    /**
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    protected function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->initializeContext();
        return parent::handle($request);
    }

    /**
     * Initializes the Context used for accessing data and finding out the current state of the application
     * Will be moved to a DI-like concept once introduced, for now, this is a singleton
     */
    protected function initializeContext()
    {
        GeneralUtility::makeInstance(Context::class, [
            'date' => new DateTimeAspect(new \DateTimeImmutable('@' . $GLOBALS['EXEC_TIME'])),
            'visibility' => new VisibilityAspect(true, true, true),
            'workspace' => new WorkspaceAspect(0),
            'backend.user' => new UserAspect(),
        ]);
    }
}
