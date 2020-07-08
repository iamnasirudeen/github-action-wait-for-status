<?php declare(strict_types=1);

namespace WyriHaximus\GithubAction\WaitForStatus;

use ApiClients\Client\Github\Resource\Async\Repository;
use ApiClients\Client\Github\Resource\Async\Repository\Commit;
use Psr\Log\LoggerInterface;
use React\Promise\PromiseInterface;
use Rx\Observable;
use function ApiClients\Tools\Rx\observableFromArray;
use function React\Promise\all;
use function React\Promise\resolve;

final class LookUpCommits
{
    private string $sha;
    private LoggerInterface $logger;

    public function __construct(string $sha, LoggerInterface $logger)
    {
        $this->sha    = $sha;
        $this->logger = $logger;
    }

    public function __invoke(Repository $repository): PromiseInterface
    {
        $this->logger->debug('Locating commit: ' . $this->sha);

        return $repository->specificCommit($this->sha)->then(static function (Commit $commit) use ($repository): PromiseInterface {
            $commits = [resolve($commit)];
            foreach ($commit->parents() as $parent) {
                $commits[] = $repository->specificCommit($parent->sha());
            }

            return all($commits);
        })->then(static function (array $commits): Observable {
            return observableFromArray($commits);
        });
    }
}