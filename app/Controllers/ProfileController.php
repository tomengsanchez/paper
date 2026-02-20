<?php
namespace App\Controllers;

use Core\Controller;
use App\Models\Profile;

class ProfileController extends Controller
{
    public function __construct()
    {
        $this->requireCapability('manage_profiles');
    }

    public function index(): void
    {
        $profiles = Profile::all();
        $this->view('profile/index', ['profiles' => $profiles]);
    }

    public function create(): void
    {
        $papsid = Profile::generatePAPSID();
        $this->view('profile/form', ['profile' => null, 'papsid' => $papsid]);
    }

    public function store(): void
    {
        Profile::create([
            'papsid' => trim($_POST['papsid'] ?? Profile::generatePAPSID()),
            'control_number' => trim($_POST['control_number'] ?? ''),
            'full_name' => trim($_POST['full_name'] ?? ''),
            'age' => (int) ($_POST['age'] ?? 0),
            'contact_number' => trim($_POST['contact_number'] ?? ''),
            'project_id' => (int) ($_POST['project_id'] ?? 0) ?: null,
        ]);
        $this->redirect('/profile');
    }

    public function edit(int $id): void
    {
        $profile = Profile::find($id);
        if (!$profile) {
            $this->redirect('/profile');
            return;
        }
        $this->view('profile/form', ['profile' => $profile]);
    }

    public function update(int $id): void
    {
        Profile::update($id, [
            'papsid' => trim($_POST['papsid'] ?? ''),
            'control_number' => trim($_POST['control_number'] ?? ''),
            'full_name' => trim($_POST['full_name'] ?? ''),
            'age' => (int) ($_POST['age'] ?? 0),
            'contact_number' => trim($_POST['contact_number'] ?? ''),
            'project_id' => (int) ($_POST['project_id'] ?? 0) ?: null,
        ]);
        $this->redirect('/profile');
    }

    public function delete(int $id): void
    {
        Profile::delete($id);
        $this->redirect('/profile');
    }
}
