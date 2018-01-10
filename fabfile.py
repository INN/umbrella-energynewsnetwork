from tools.fablib import *

from fabric.api import task


"""
Base configuration
"""
env.project_name = 'usenergynews'       # name for the project.
env.hosts = ['localhost', ]
env.sftp_deploy = True
env.domain = 'usenergynews.dev'


"""
Add HipChat info to send a message to a room when new code has been deployed.
"""
env.hipchat_token = ''
env.hipchat_room_id = ''


# Environments
@task
def production():
    """
    Work on production environment
    """
    env.settings    = 'production'
    env.hosts       = [ os.environ[ 'USENERGYNEWS_PRODUCTION_SFTP_HOST' ], ]   # ssh host for production.
    env.user        = os.environ[ 'USENERGYNEWS_PRODUCTION_SFTP_USER' ]        # ssh user for production.
    env.password    = os.environ[ 'USENERGYNEWS_PRODUCTION_SFTP_PASSWORD' ]    # ssh password for production.
    env.domain      = 'usenergynews.wpengine.com'
    env.port        = '2222'



@task
def staging():
    """
    Work on staging environment
    """
    env.settings    = 'staging'
    env.hosts       = [ os.environ[ 'USENERGYNEWS_STAGING_SFTP_HOST' ], ]   # ssh host for production.
    env.user        = os.environ[ 'USENERGYNEWS_STAGING_SFTP_USER' ]        # ssh user for production.
    env.password    = os.environ[ 'USENERGYNEWS_STAGING_SFTP_PASSWORD' ]    # ssh password for production.
    env.domain      = 'usenergynews.staging.wpengine.com'
    env.port        = '2222'

try:
    from local_fabfile import  *
except ImportError:
    pass
