<?php

namespace App\Controller;

use App\Entity\Gallery;
use App\Entity\Image;
use App\Service\FileManager;
use App\Service\UserManager;
use Doctrine\ORM\EntityManager;
use Ramsey\Uuid\Uuid;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\Routing\RouterInterface;
use Twig_Environment;

class UploadController
{
    /** @var  Twig_Environment */
    private $twig;

    /** @var  FlashBagInterface */
    private $flashBag;

    /** @var  RouterInterface */
    private $router;

    /** @var FileManager */
    private $fileManager;

    /** @var  EntityManager */
    private $em;

    /** @var  UserManager */
    private $userManager;

    public function __construct(
        Twig_Environment $twig,
        FlashBagInterface $flashBag,
        RouterInterface $router,
        FileManager $fileManager,
        EntityManager $em,
        UserManager $userManager
    ) {
        $this->twig = $twig;
        $this->flashBag = $flashBag;
        $this->router = $router;
        $this->fileManager = $fileManager;
        $this->em = $em;
        $this->userManager = $userManager;
    }

    /**
     * @Route("/private/upload", name="upload")
     */
    public function renderUploadScreenAction(Request $request)
    {
        $view = $this->twig->render('gallery/upload.html.twig');

        return new Response($view);
    }

    /**
     * @Route("/private/upload-process", name="upload.process")
     */
    public function processUploadAction(Request $request)
    {
        // @todo access control
        // @todo input validation

        $gallery = new Gallery(Uuid::getFactory()->uuid4());
        $gallery->setName($request->get('name', null));
        $gallery->setDescription($request->get('description', null));
        $gallery->setUser($this->userManager->getCurrentUser());
        $files = $request->files->get('file');

        /** @var UploadedFile $file */
        foreach ($files as $file) {
            $filename = $file->getClientOriginalName();
            $filepath = Uuid::getFactory()->uuid4();
            $movedFile = $this->fileManager->upload($file, $filepath);

            $image = new Image(
                Uuid::getFactory()->uuid4(),
                $filename,
                $filepath
            );

            $gallery->addImage($image);
        }

        $this->em->persist($gallery);
        $this->em->flush();

        $this->flashBag->add('success', 'Gallery created!');

        return new JsonResponse([
            'success'     => true,
            'redirectUrl' => $this->router->generate(
                'gallery.single-gallery',
                ['id' => $gallery->getId()]
            ),
        ]);
    }

}
