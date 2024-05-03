<?php


namespace App\Service;

use App\Utils\ValidationUtility;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Constraints\Uuid as UuidConstraint;
use App\Utils\ContainerUtility;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use App\Utils\GeneralUtility;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use App\Entity\Feedback;
use App\Entity\ResetObject;
use App\Entity\Property;

/**
 * SuperAdminService
 *
 * SuperAdmin service actions.
 *
 * @package         BaluProperty
 * @subpackage      AppBundle
 * @author          pitsolutions.ch
 */
class SuperAdminService
{
    /**
     * @var ManagerRegistry $doctrine
     */
    private ManagerRegistry $doctrine;

    /**
     * @var ContainerUtility $containerUtility
     */
    private ContainerUtility $containerUtility;

    /**
     * @var ContainerUtility $containerUtility
     */
    private GeneralUtility $generalUtility;

    /**
     * @var ParameterBagInterface $params
     */
    private ParameterBagInterface $params;

    /**
     * UserService constructor.
     * @param ManagerRegistry $doctrine
     * @param ContainerUtility $containerUtility
     * @param ParameterBagInterface $params
     */
    public function __construct(ManagerRegistry $doctrine, ContainerUtility $containerUtility, ParameterBagInterface $params, GeneralUtility $generalUtility)
    {
        $this->doctrine = $doctrine;
        $this->containerUtility = $containerUtility;
        $this->params = $params;
        $this->generalUtility = $generalUtility;
    }

    /**
     *
     * @return array
     */
    public function getFeedbacks(array $params = []): array
    {
        $em = $this->doctrine->getManager();
        $feedBacks = $em->getRepository(Feedback::class)->getFeedbackDetails($params);
        $data = [];
        foreach ($feedBacks as $key => $feedBack) {
            if ($feedBack instanceof Feedback) {
                $data[$key]['subject'] = $feedBack->getSubject();
                $data[$key]['message'] = $feedBack->getMessage();
                $data[$key]['sendBy']['uuid'] = $feedBack->getSendBy()->getPublicId();
                $data[$key]['sendBy']['name'] = $feedBack->getSendBy()->getFirstName() . ' ' . $feedBack->getSendBy()->getLastName();
            }
        }

        return $data;
    }

    /**
     *
     * @param Property $property
     * @param Request $request
     * @return void
     * @throws ResourceNotFoundException
     */
    public function resetObjects(Property $property, Request $request): void
    {
        $em = $this->doctrine->getManager();
        if (!empty($objects = $request->get('objects'))) {
            foreach ($objects as $object) {
                $oObject = $em->getRepository(ResetObject::class)->findOneBy(['publicId' => $object, 'property' => $property, 'deleted' => 0]);
                if (!$oObject instanceof ResetObject) {
                    throw new ResourceNotFoundException('objectNotFound');
                }
                $oObject->getApartment()->setActive(false);
                $oObject->setIsSuperAdminApproved(true)
                    ->setSuperAdminComment('');
            }
        }
    }
}
