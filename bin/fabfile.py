# -*- coding: utf-8 -*-

from fabric.api import *
from fabric.colors import green, red
from fabric.state import output
from fabric.utils import puts


verbose = True
# Set value for not verbose execution
if 'verbose' not in env.keys():
    verbose = False
    output.stdout = False
    output.stderr = False
    output.running = False
    output.warnings = False
    output.status = False
    output.output = False


def pull(remote, branch):
    """
    Pull changes from specified branch and remote
    :param remote: remote name
    :param branch: branch name
    :return:
    """
    command = 'git fetch && git checkout %s && git pull %s %s' % (branch, remote, branch)
    message = u'Updating source code'
    _run_command(command, message)


def cache_clear(php_bin, console_bin, environment):
    """
    Clears the cache for required environment
    :param php_bin: path to php executable
    :param console_bin: relative path to console executable
    :param environment: environment to clear the cache
    :return:
    """
    command = '%s %s cache:clear --env=%s' % (php_bin, console_bin, environment)
    message = u'Clearing cache for %s environment' % environment
    _run_command(command, message)


def composer_update(php_bin, composer_bin, memory_limit=False):
    """
    Updates composer for project
    :param php_bin: path to php executable
    :param composer_bin: path to composer executable
    :param memory_limit: memory limit for composer update
    :return:
    """
    if memory_limit:
        command = '%s -d memory_limit=%s %s update' % (php_bin, memory_limit, composer_bin)
    else:
        command = '%s %s update' % (php_bin, composer_bin)

    print command
    exit(0)

    message = u'Updating composer'
    _run_command(command, message)


def assets_install(php_bin, console_bin, web_path, symlink=False, relative=False):
    """
    Install assets for project with desired options
    :param php_bin: path to php executable
    :param console_bin: relative path to console executable
    :param web_path: relative path to console executable
    :param symlink: whether to install assets as symlink
    :param relative: whether to install assets as relative
    :return:
    """
    command = '%s %s assets:install %s' % (php_bin, console_bin, web_path)

    if symlink:
        command = '%s --symlink' % command

    if relative:
        command = '%s --relative' % command

    message = u'Installing assets'

    _run_command(command, message)


def database_migration(php_bin, console_bin, interaction=False):
    """
    Execute database migrations
    :param php_bin: path to php executable
    :param console_bin: relative path to console executable
    :param interaction: whether to enable interaction of migrations bundle or not
    :return:
    """
    command = '%s %s doctrine:migrations:migrate --no-interaction' % (php_bin, console_bin)
    message = u'Executing database migrations'
    _run_command(command, message)


def _print_output(message, end='', padding=True):
    """
    Aux function for printing messages if verbose is not enabled
    :param message: the message to print
    :param end: the character to print at line end
    :return:
    """
    if not verbose:
        if padding:
            puts(green(u'{:.<100}'.format(message), bold=True), end=end, show_prefix=False, flush=True)
        else:
            puts(green(message, bold=True), end=end, show_prefix=False, flush=True)


def _print_ok():
    """
    Aux function for printing a tick
    :return:
    """
    # _print_output(u'\u2714', '\n', False)
    _print_output(u'OK', '\n', False)


def _print_ko():
    """
    Aux function for printing a cross
    :return:
    """
    # puts(red(u'\u2718', bold=True), end='\n', show_prefix=False, flush=True)
    puts(red(u'KO', bold=True), end='\n', show_prefix=False, flush=True)


def _run_command(command, message):
    """
    Aux function to execute commands
    :param command:
    :param message:
    :return:
    """
    _print_output(message)
    with cd(env.path):
        result = run(command, warn_only=verbose)
        if result.succeeded:
            _print_ok()
        else:
            _print_ko()
            puts(red(result))
