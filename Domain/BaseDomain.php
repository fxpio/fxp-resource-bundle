<?php

/*
 * This file is part of the Sonatra package.
 *
 * (c) François Pluchino <francois.pluchino@sonatra.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonatra\Bundle\ResourceBundle\Domain;

use Doctrine\DBAL\Exception\DriverException;
use Sonatra\Bundle\ResourceBundle\Resource\ResourceInterface;
use Sonatra\Bundle\ResourceBundle\Resource\ResourceListInterface;
use Sonatra\Bundle\ResourceBundle\ResourceStatutes;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\ConstraintViolationListInterface;

/**
 * A base class for resource domain.
 *
 * @author François Pluchino <francois.pluchino@sonatra.com>
 */
abstract class BaseDomain extends AbstractDomain
{
    /**
     * Do the flush transaction for auto commit.
     *
     * @param ResourceInterface $resource   The resource
     * @param bool              $autoCommit The auto commit
     * @param bool              $skipped    Check if the resource is skipped
     *
     * @return bool Returns if there is an flush error
     */
    protected function doAutoCommitFlushTransaction(ResourceInterface $resource, $autoCommit, $skipped = false)
    {
        $hasFlushError = false;

        if ($autoCommit && !$skipped) {
            $rErrors = $this->flushTransaction($resource->getRealData());
            $resource->getErrors()->addAll($rErrors);
            $hasFlushError = $rErrors->count() > 0;
        }

        return $hasFlushError;
    }

    /**
     * Do flush the final transaction for non auto commit.
     *
     * @param ResourceListInterface $resources  The list of object resource instance
     * @param bool                  $autoCommit Commit transaction for each resource or all
     *                                          (continue the action even if there is an error on a resource)
     * @param bool                  $hasError   Check if there is an error
     *
     * @return bool Check if there is an error in list
     */
    protected function doFlushFinalTransaction(ResourceListInterface $resources, $autoCommit, $hasError)
    {
        if (!$autoCommit) {
            if ($hasError) {
                $this->cancelTransaction();
            } else {
                $errors = $this->flushTransaction();

                if (count($errors) > 0) {
                    $resources->getErrors()->addAll($errors);
                    foreach ($resources as $resource) {
                        $resource->setStatus(ResourceStatutes::ERROR);
                    }
                }
            }
        }
    }

    /**
     * Finalize the action for a resource.
     *
     * @param ResourceInterface $resource
     * @param $status
     * @param $hasError
     *
     * @return bool Returns the new hasError value
     */
    protected function finalizeResourceStatus(ResourceInterface $resource, $status, $hasError)
    {
        if ($resource->isValid()) {
            $resource->setStatus($status);
        } else {
            $hasError = true;
            $resource->setStatus(ResourceStatutes::ERROR);
            $this->om->detach($resource->getRealData());
        }

        return $hasError;
    }

    /**
     * Begin automatically the database transaction.
     *
     * @param bool $autoCommit Check if each resource must be flushed immediately or in the end
     */
    protected function beginTransaction($autoCommit = false)
    {
        if (!$autoCommit && null !== $this->connection) {
            $this->connection->beginTransaction();
        }
    }

    /**
     * Flush data in database with automatic declaration of the transaction for the collection.
     *
     * @param object|null $object The resource for auto commit or null for flush at the end
     *
     * @return ConstraintViolationList
     */
    protected function flushTransaction($object = null)
    {
        $violations = new ConstraintViolationList();

        try {
            $this->flush($object);

            if (null !== $this->connection && null === $object) {
                $this->connection->commit();
            }
        } catch (\Exception $e) {
            $this->flushTransactionException($e, $violations, $object);
        }

        return $violations;
    }

    /**
     * Do the action when there is an exception on flush transaction.
     *
     * @param \Exception                       $e          The exception on flush transaction
     * @param ConstraintViolationListInterface $violations The constraint violation list
     * @param object|null                      $object     The resource for auto commit or null for flush at the end
     */
    protected function flushTransactionException(\Exception $e, ConstraintViolationListInterface $violations, $object = null)
    {
        if (null !== $this->connection && null === $object) {
            $this->connection->rollback();
        }

        $message = $e instanceof DriverException
            ? DomainUtil::extractDriverExceptionMessage($e, $this->debug)
            : $e->getMessage();

        $violations->add(new ConstraintViolation($message, $message, array(), null, null, null));
    }

    /**
     * Cancel transaction.
     */
    protected function cancelTransaction()
    {
        if (null !== $this->connection) {
            $this->connection->rollBack();
        }
    }

    /**
     * Flush the object data in database.
     *
     * @param object|null $object The resource data for auto commit or null for flush at the end
     */
    protected function flush($object = null)
    {
        $this->om->flush();

        if (null !== $object) {
            $this->om->detach($object);
        } else {
            $this->om->clear();
        }
    }

    /**
     * Validate the resource and get the error list.
     *
     * @param ResourceInterface $resource The resource
     * @param int               $type     The type of persist
     */
    protected function validateResource($resource, $type)
    {
        $idError = $this->getErrorIdentifier($resource->getRealData(), $type);
        $data = $resource->getData();

        if ($data instanceof FormInterface) {
            if (!$data->isSubmitted()) {
                $data->submit(array());
            }
        } else {
            $errors = $this->validator->validate($data);
            $resource->getErrors()->addAll($errors);
        }

        if (null !== $idError) {
            $resource->getErrors()->add(new ConstraintViolation($idError, $idError, array(), '', '', ''));
        }
    }

    /**
     * Get the error of identifier.
     *
     * @param object $object The object data
     * @param int    $type   The type of persist
     *
     * @return string|null
     */
    protected function getErrorIdentifier($object, $type)
    {
        $idValue = DomainUtil::getIdentifier($this->om, $object);
        $idError = null;

        if (Domain::TYPE_CREATE === $type && null !== $idValue) {
            $idError = 'The resource cannot be created because it has an identifier';
        } elseif (Domain::TYPE_UPDATE === $type && null === $idValue) {
            $idError = 'The resource cannot be updated because it has not an identifier';
        } elseif (Domain::TYPE_DELETE === $type && null === $idValue) {
            $idError = 'The resource cannot be deleted because it has not an identifier';
        }

        return $idError;
    }

    /**
     * Get the success status.
     *
     * @param int    $type   The type of persist
     * @param object $object The resource instance
     *
     * @return string
     */
    protected function getSuccessStatus($type, $object)
    {
        if (Domain::TYPE_CREATE === $type) {
            return ResourceStatutes::CREATED;
        }
        if (Domain::TYPE_UPDATE === $type) {
            return ResourceStatutes::UPDATED;
        }

        return null === DomainUtil::getIdentifier($this->om, $object)
            ? ResourceStatutes::CREATED
            : ResourceStatutes::UPDATED;
    }
}
